<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\SupplierManagement\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\SupplierManagement\Models;

use phpOMS\DataStorage\Database\DataMapperAbstract;

/**
 * Supplier mapper class.
 *
 * @package Modules\SupplierManagement\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class SupplierAttributeTypeL11nMapper extends DataMapperAbstract
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    protected static array $columns = [
        'suppliermgmt_attr_type_l11n_id'        => ['name' => 'suppliermgmt_attr_type_l11n_id',       'type' => 'int',    'internal' => 'id'],
        'suppliermgmt_attr_type_l11n_title'     => ['name' => 'suppliermgmt_attr_type_l11n_title',    'type' => 'string', 'internal' => 'title', 'autocomplete' => true],
        'suppliermgmt_attr_type_l11n_type'      => ['name' => 'suppliermgmt_attr_type_l11n_type',      'type' => 'int',    'internal' => 'type'],
        'suppliermgmt_attr_type_l11n_lang'      => ['name' => 'suppliermgmt_attr_type_l11n_lang', 'type' => 'string', 'internal' => 'language'],
    ];

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $table = 'suppliermgmt_attr_type_l11n';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $primaryField = 'suppliermgmt_attr_type_l11n_id';
}
