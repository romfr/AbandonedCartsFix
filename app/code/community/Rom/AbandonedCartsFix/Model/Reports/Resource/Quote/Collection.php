<?php
/**
 * @category    Rom
 * @package     Rom_AbandonedCartsFix
 * @copyright   Copyright (c) 2016 ROM - Agence de communication (http://www.rom.fr/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author      André Herrn <andre.herrn@rom.fr>
 */
class Rom_AbandonedCartsFix_Model_Reports_Resource_Quote_Collection extends Mage_Reports_Model_Resource_Quote_Collection
{
    /**
     * Add customer data
     *
     * @param unknown_type $filter
     * @return Mage_Reports_Model_Resource_Quote_Collection
     */
    public function addCustomerData($filter = null)
    {
        $customerEntity          = Mage::getResourceSingleton('customer/customer');
        $attrFirstname           = $customerEntity->getAttribute('firstname');
        $attrFirstnameId         = (int) $attrFirstname->getAttributeId();
        $attrFirstnameTableName  = $attrFirstname->getBackend()->getTable();

        $attrLastname            = $customerEntity->getAttribute('lastname');
        $attrLastnameId          = (int) $attrLastname->getAttributeId();
        $attrLastnameTableName   = $attrLastname->getBackend()->getTable();

        $attrMiddlename          = $customerEntity->getAttribute('middlename');
        $attrMiddlenameId        = (int) $attrMiddlename->getAttributeId();
        $attrMiddlenameTableName = $attrMiddlename->getBackend()->getTable();

        $attrEmail       = $customerEntity->getAttribute('email');
        $attrEmailTableName = $attrEmail->getBackend()->getTable();

        $adapter = $this->getSelect()->getAdapter();
        $customerName = $adapter->getConcatSql(array('cust_fname.value', 'cust_mname.value', 'cust_lname.value',), ' ');
        $this->getSelect()
            ->joinInner(
                array('cust_email' => $attrEmailTableName),
                'cust_email.entity_id = main_table.customer_id',
                array('email' => 'cust_email.email')
            )
            ->joinInner(
                array('cust_fname' => $attrFirstnameTableName),
                implode(' AND ', array(
                    'cust_fname.entity_id = main_table.customer_id',
                    $adapter->quoteInto('cust_fname.attribute_id = ?', (int) $attrFirstnameId),
                )),
                array('firstname' => 'cust_fname.value')
            )
            //Rom_AbandonedCartsFix: change from joinInner to joinLeft
            ->joinLeft(
                array('cust_mname' => $attrMiddlenameTableName),
                implode(' AND ', array(
                    'cust_mname.entity_id = main_table.customer_id',
                    $adapter->quoteInto('cust_mname.attribute_id = ?', (int) $attrMiddlenameId),
                )),
                array('middlename' => 'cust_mname.value')
            )
            ->joinInner(
                array('cust_lname' => $attrLastnameTableName),
                implode(' AND ', array(
                    'cust_lname.entity_id = main_table.customer_id',
                     $adapter->quoteInto('cust_lname.attribute_id = ?', (int) $attrLastnameId)
                )),
                array(
                    'lastname'      => 'cust_lname.value',
                    'customer_name' => $customerName
                )
            );

        $this->_joinedFields['customer_name'] = $customerName;
        $this->_joinedFields['email']         = 'cust_email.email';

        if ($filter) {
            if (isset($filter['customer_name'])) {
                $likeExpr = '%' . $filter['customer_name'] . '%';
                $this->getSelect()->where($this->_joinedFields['customer_name'] . ' LIKE ?', $likeExpr);
            }
            if (isset($filter['email'])) {
                $likeExpr = '%' . $filter['email'] . '%';
                $this->getSelect()->where($this->_joinedFields['email'] . ' LIKE ?', $likeExpr);
            }
        }

        return $this;
    }
}
