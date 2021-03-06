<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\SupplierManagement
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\SupplierManagement\Controller;

use Modules\Admin\Models\Account;
use Modules\Admin\Models\Address;
use Modules\Media\Models\PathSettings;
use Modules\Profile\Models\ContactElementMapper;
use Modules\Profile\Models\Profile;
use Modules\SupplierManagement\Models\AttributeValueType;
use Modules\SupplierManagement\Models\NullSupplierAttributeType;
use Modules\SupplierManagement\Models\NullSupplierAttributeValue;
use Modules\SupplierManagement\Models\Supplier;
use Modules\SupplierManagement\Models\SupplierAttribute;
use Modules\SupplierManagement\Models\SupplierAttributeMapper;
use Modules\SupplierManagement\Models\SupplierAttributeType;
use Modules\SupplierManagement\Models\SupplierAttributeTypeL11n;
use Modules\SupplierManagement\Models\SupplierAttributeTypeL11nMapper;
use Modules\SupplierManagement\Models\SupplierAttributeTypeMapper;
use Modules\SupplierManagement\Models\SupplierAttributeValue;
use Modules\SupplierManagement\Models\SupplierAttributeValueMapper;
use Modules\SupplierManagement\Models\SupplierMapper;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\Message\NotificationLevel;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Message\FormValidation;

/**
 * SupplierManagement class.
 *
 * @package Modules\SupplierManagement
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class ApiController extends Controller
{
    /**
     * Api method to create news article
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiSupplierCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateSupplierCreate($request))) {
            $response->set('supplier_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $supplier = $this->createSupplierFromRequest($request);
        $this->createModel($request->header->account, $supplier, SupplierMapper::class, 'supplier', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Supplier', 'Supplier successfully created', $supplier);
    }

    /**
     * Method to create news article from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return Supplier
     *
     * @since 1.0.0
     */
    private function createSupplierFromRequest(RequestAbstract $request) : Supplier
    {
        $account        = new Account();
        $account->name1 = $request->getData('name1') ?? '';
        $account->name2 = $request->getData('name2') ?? '';

        $profile = new Profile($account);

        $supplier          = new Supplier();
        $supplier->number  = $request->getData('number') ?? '';
        $supplier->profile = $profile;

        $addr          = new Address();
        $addr->address = $request->getData('address') ?? '';
        $addr->postal  = $request->getData('postal') ?? '';
        $addr->city    = $request->getData('city') ?? '';
        $addr->state   = $request->getData('state') ?? '';
        $addr->setCountry($request->getData('country') ?? '');
        $supplier->mainAddress = $addr;

        return $supplier;
    }

    /**
     * Validate news create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateSupplierCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['number'] = empty($request->getData('number')))
            || ($val['name1'] = empty($request->getData('name1')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiContactElementCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $profileModule = $this->app->moduleManager->get('Profile');

        if (!empty($val = $profileModule->validateContactElementCreate($request))) {
            $response->set('contact_element_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $contactElement = $profileModule->createContactElementFromRequest($request);

        $this->createModel($request->header->account, $contactElement, ContactElementMapper::class, 'supplier-contactElement', $request->getOrigin());
        $this->createModelRelation(
            $request->header->account,
            (int) $request->getData('supplier'),
            $contactElement->getId(),
        SupplierMapper::class, 'contactElements', '', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Contact Element', 'Contact element successfully created', $contactElement);
    }

    /**
     * Api method to create supplier attribute
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiSupplierAttributeCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateSupplierAttributeCreate($request))) {
            $response->set('attribute_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $attribute = $this->createSupplierAttributeFromRequest($request);
        $this->createModel($request->header->account, $attribute, SupplierAttributeMapper::class, 'attribute', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Attribute', 'Attribute successfully created', $attribute);
    }

    /**
     * Method to create supplier attribute from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return SupplierAttribute
     *
     * @since 1.0.0
     */
    private function createSupplierAttributeFromRequest(RequestAbstract $request) : SupplierAttribute
    {
        $attribute           = new SupplierAttribute();
        $attribute->supplier = (int) $request->getData('supplier');
        $attribute->type     = new NullSupplierAttributeType((int) $request->getData('type'));
        $attribute->value    = new NullSupplierAttributeValue((int) $request->getData('value'));

        return $attribute;
    }

    /**
     * Validate supplier attribute create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateSupplierAttributeCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['type'] = empty($request->getData('type')))
            || ($val['value'] = empty($request->getData('value')))
            || ($val['supplier'] = empty($request->getData('supplier')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create supplier attribute l11n
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiSupplierAttributeTypeL11nCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateSupplierAttributeTypeL11nCreate($request))) {
            $response->set('attr_type_l11n_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $attrL11n = $this->createSupplierAttributeTypeL11nFromRequest($request);
        $this->createModel($request->header->account, $attrL11n, SupplierAttributeTypeL11nMapper::class, 'attr_type_l11n', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Attribute type localization', 'Attribute type localization successfully created', $attrL11n);
    }

    /**
     * Method to create supplier attribute l11n from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return SupplierAttributeTypeL11n
     *
     * @since 1.0.0
     */
    private function createSupplierAttributeTypeL11nFromRequest(RequestAbstract $request) : SupplierAttributeTypeL11n
    {
        $attrL11n = new SupplierAttributeTypeL11n();
        $attrL11n->setType((int) ($request->getData('type') ?? 0));
        $attrL11n->setLanguage((string) (
            $request->getData('language') ?? $request->getLanguage()
        ));
        $attrL11n->title = (string) ($request->getData('title') ?? '');

        return $attrL11n;
    }

    /**
     * Validate supplier attribute l11n create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateSupplierAttributeTypeL11nCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['title'] = empty($request->getData('title')))
            || ($val['type'] = empty($request->getData('type')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create supplier attribute type
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiSupplierAttributeTypeCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateSupplierAttributeTypeCreate($request))) {
            $response->set('attr_type_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $attrType = $this->createSupplierAttributeTypeFromRequest($request);
        $attrType->setL11n($request->getData('title'), $request->getData('language'));
        $this->createModel($request->header->account, $attrType, SupplierAttributeTypeMapper::class, 'attr_type', $request->getOrigin());

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Attribute type', 'Attribute type successfully created', $attrType);
    }

    /**
     * Method to create supplier attribute from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return SupplierAttributeType
     *
     * @since 1.0.0
     */
    private function createSupplierAttributeTypeFromRequest(RequestAbstract $request) : SupplierAttributeType
    {
        $attrType = new SupplierAttributeType();
        $attrType->setL11n((string) ($request->getData('name') ?? ''));
        $attrType->setFields((int) ($request->getData('fields') ?? 0));
        $attrType->setCustom((bool) ($request->getData('custom') ?? false));

        return $attrType;
    }

    /**
     * Validate supplier attribute create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateSupplierAttributeTypeCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['name'] = empty($request->getData('name')))
            || ($val['title'] = empty($request->getData('title')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create supplier attribute value
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiSupplierAttributeValueCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateSupplierAttributeValueCreate($request))) {
            $response->set('attr_value_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $attrValue = $this->createSupplierAttributeValueFromRequest($request);
        $this->createModel($request->header->account, $attrValue, SupplierAttributeValueMapper::class, 'attr_value', $request->getOrigin());

        if ($attrValue->isDefault) {
            $this->createModelRelation(
                $request->header->account,
                (int) $request->getData('attributetype'),
                $attrValue->getId(),
                SupplierAttributeTypeMapper::class, 'defaults', '', $request->getOrigin()
            );
        }

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Attribute value', 'Attribute value successfully created', $attrValue);
    }

    /**
     * Method to create supplier attribute value from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return SupplierAttributeValue
     *
     * @since 1.0.0
     */
    private function createSupplierAttributeValueFromRequest(RequestAbstract $request) : SupplierAttributeValue
    {
        $attrValue = new SupplierAttributeValue();

        $type = $request->getData('type') ?? 0;
        if ($type === AttributeValueType::_INT) {
            $attrValue->valueInt = (int) $request->getData('value');
        } elseif ($type === AttributeValueType::_STRING) {
            $attrValue->valueStr = (string) $request->getData('value');
        } elseif ($type === AttributeValueType::_FLOAT) {
            $attrValue->valueDec = (float) $request->getData('value');
        } elseif ($type === AttributeValueType::_DATETIME) {
            $attrValue->valueDat = new \DateTime($request->getData('value') ?? '');
        }

        $attrValue->type      = $type;
        $attrValue->isDefault = (bool) ($request->getData('default') ?? false);

        if ($request->hasData('language')) {
            $attrValue->setLanguage((string) ($request->getData('language') ?? $request->getLanguage()));
        }

        if ($request->hasData('country')) {
            $attrValue->setCountry((string) ($request->getData('country') ?? $request->header->l11n->getCountry()));
        }

        return $attrValue;
    }

    /**
     * Validate supplier attribute value create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateSupplierAttributeValueCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['type'] = empty($request->getData('type')))
            || ($val['value'] = empty($request->getData('value')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create supplier files
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiFileCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $uploadedFiles = $request->getFiles() ?? [];

        if (empty($uploadedFiles)) {
            $this->fillJsonResponse($request, $response, NotificationLevel::ERROR, 'Item', 'Invalid supplier image', $uploadedFiles);
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $uploaded = $this->app->moduleManager->get('Media')->uploadFiles(
            [$request->getData('name') ?? ''],
            $uploadedFiles,
            $request->header->account,
            __DIR__ . '/../../../Modules/Media/Files/Modules/SupplierManagement/' . ($request->getData('supplier') ?? '0'),
            '/Modules/SupplierManagement/' . ($request->getData('supplier') ?? '0'),
            $request->getData('type') ?? '',
            '',
            '',
            PathSettings::FILE_PATH
        );

        $this->createModelRelation(
            $request->header->account,
            (int) $request->getData('supplier'),
            \reset($uploaded)->getId(),
            SupplierMapper::class, 'files', '', $request->getOrigin()
        );

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Image', 'Image successfully updated', $uploaded);
    }

    /**
     * Api method to create supplier files
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiNoteCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $request->setData('virtualpath', '/Modules/SupplierManagement/' . $request->getData('id'), true);
        $this->app->moduleManager->get('Editor')->apiEditorCreate($request, $response, $data);

        $model = $response->get($request->uri->__toString())['response'];
        $this->createModelRelation($request->header->account, $request->getData('id'), $model->getId(), SupplierMapper::class, 'notes', '', $request->getOrigin());
    }
}
