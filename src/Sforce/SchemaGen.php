<?php

namespace SForce;

/**
 * This class generates simplified DDL schema for a given SF connection. The generated
 * DDL is particularly useful when developing SOQL queries; you can configure your IDE
 * to use the generated SQL file for hinting (eg; "DDL Data Source" in PhpStorm).
 */
class SchemaGen
{
    /**
     * @var Client\Base
     */
    private $sfClient;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string Default content for empty ENUMs (since empty enum is not valid sql).
     */
    protected static $EMPTY_ENUM = "'#empty#'";

    /**
     * @var string Default content for empty SETs (since empty set is not valid sql).
     */
    protected static $EMPTY_SET = "'#empty#'";

    /**
     * @param Client\Base $sfClient SalesForce client.
     * @param string $output Output destination path.
     */
    public function __construct(Client\Base $sfClient, $output)
    {
        $this->sfClient = $sfClient;
        $this->output = $output;
    }

    public function generate()
    {
        $globalDesc = $this->sfClient->describeGlobal();

        $fh = fopen($this->output, 'wb');
        try {
            foreach ($globalDesc->sobjects as $globalEntityDesc) {
                $entity = $this->sfClient->describeSObject($globalEntityDesc->name);
                fwrite($fh, PHP_EOL . $this->generateTableDDL($entity) . PHP_EOL);
            }
        } finally {
            fclose($fh);
        }
    }

    /**
     * @param \stdClass $entity
     *
     * @return string
     */
    protected function generateTableDDL($entity)
    {
        static $prefix = ',' . PHP_EOL . '    ';

        return sprintf(
            'CREATE TABLE %s (%s    %s%s);',
            $entity->name,
            PHP_EOL,
            implode(
                $prefix,
                array_map(
                    [$this, 'generateFieldDDL'],
                    $entity->fields
                )
            ),
            PHP_EOL
        );
    }

    /**
     * @param \stdClass $field
     *
     * @return string
     */
    protected function generateFieldDDL($field)
    {
        $allowNull = $field->nillable ? 'NULL' : 'NOT NULL';
        $varchar = 'VARCHAR(' . ($field->length ?: 'MAX') . ')';
        $decimal = "DECIMAL({$field->precision}, {$field->scale})";
        $integer = "INT({$field->digits})";

        switch ($field->type) {
            case 'id':
                return "$field->name $varchar PRIMARY KEY";
            case 'boolean':
                return "$field->name BIT $allowNull";
            case 'url':
            case 'phone':
            case 'email':
            case 'string':
            case 'textarea':
            case 'combobox': // mix between picklist and open text
                return "$field->name $varchar $allowNull";
            case 'picklist':
                if (!isset($field->picklistValues) || !$field->picklistValues) {
                    return "$field->name ENUM(" . static::$EMPTY_ENUM . ") $allowNull";
                }

                return "$field->name ENUM('"
                       . implode(
                           "', '",
                           array_map(
                               function ($pickListValue) {
                                   return $pickListValue->value;
                               },
                               $field->picklistValues
                           )
                       ) . "') $allowNull";
            case 'multipicklist':
                if (!isset($field->picklistValues) || !$field->picklistValues) {
                    return "$field->name SET(" . static::$EMPTY_SET . ") $allowNull";
                }

                return "$field->name SET('"
                       . implode(
                           "', '",
                           array_map(
                               function ($pickListValue) {
                                   return $pickListValue->value;
                               },
                               $field->picklistValues
                           )
                       ) . "') $allowNull";
            case 'int':
                return "$field->name $integer $allowNull";
            case 'double':
            case 'percent':
            case 'currency':
                return "$field->name $decimal $allowNull";
            case 'date':
            case 'time':
            case 'datetime':
                return "$field->name " . strtoupper($field->type) . " $allowNull";
            case 'reference':
                return "$field->name $varchar $allowNull"; // TODO foreign key to referenceTo[0]..
            case 'base64':
                return "$field->name TEXT $allowNull";
            case 'anyType':
                return "$field->name TEXT $allowNull";

            default:
                throw new \RuntimeException("Unsupported field type: $field->type");
        }
    }
}
