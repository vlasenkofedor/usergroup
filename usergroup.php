<?php
/**
 * UserGroup
 *
 * @version        1.0
 * @author         Fedor Vlasenko vladsenkofedor@mail.ru
 * @url            fregate.org.ua
 * @copyright      © 2013. All rights reserved.
 * @license        GNU/GPL v.3 or later.
 */

// no direct access
defined('_JEXEC') or die('(@)|(@)');

jimport('joomla.plugin.plugin');

class plgUserUserGroup extends JPlugin
{
    private $groups = array();
    private $del_groups;
    private $del_name;
    private $del_username;
    private $del_password1;
    private $del_password2;
    private $del_email2;

    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        $this->def_groups = JComponentHelper::getParams('com_users')->get('new_usertype', 2);
        $this->groups = $this->params->get('groups', array($this->def_groups));
        $this->del_groups = $this->params->get('del_groups', 0);
        $this->del_name = $this->params->get('del_name', 0);
        $this->del_username = $this->params->get('del_username', 0);
        $this->del_password1 = $this->params->get('del_password1', 0);
        $this->del_password2 = $this->params->get('del_password2', 0);
        $this->del_email2 = $this->params->get('del_email2', 0);

    }


    public function onContentPrepareData($context, $data)
    {
        if ($context != 'com_users.registration' || !is_object($data)) {
            return true;
        }

        $app = JFactory::getApplication();
        $temp = $app->input->get('jform', array(), 'array', 'post');
        
        if (isset($temp['password1'])){
             $data->password1 = $temp['password1'];
         }

        if (empty($temp['email1'])) {
            return true;
        }

        if (!isset($data->email)) {
            $data->email1 = $temp['email1'];
        }

        if ($this->del_email2) {
            $data->email2 = $data->email1;
        }

        if ($this->del_name) {
            $name = explode('@', $data->email1);
            $data->name = $name[0];
        }

        if ($this->del_username) {
            $data->username = $data->email1;
        }

        if (isset($temp['usergroup']) && in_array($temp['usergroup'], $this->groups)) {
            $data->groups = array($temp['usergroup']);
        } else {
           $data->groups = array($this->def_groups);
        }

        if ($this->del_password1) {
            $data->password1 = JUserHelper::genRandomPassword($this->params->get('pass_length', 8));
        }

        if ($this->del_password2) {
            $data->password2 = $data->password1;
        }

        //die(var_dump($data));

        return true;
    }

    function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');

            return false;
        }

        if ($form->getName() != 'com_users.registration') {
            return true;
        }

        if ($this->del_name) {
            $form->removeField('name');
        }

        if ($this->del_username) {
            $form->removeField('username');
        }

        if ($this->del_password1) {
            $form->removeField('password1');
        }

        if ($this->del_password2) {
            $form->removeField('password2');
            $form->setFieldAttribute('password1', 'validate',null, null);
        }

        if ($this->del_email2) {
            $form->removeField('email2');
        }

        if ($this->del_groups) {
            return true;
        }

        $xmlform = new SimpleXMLElement('<form />');
        $xmlfields = $xmlform->addChild('fields');
        $xmlfieldset = $xmlfields->addChild('fieldset');
        $xmlfieldset->addAttribute('name', 'default');
        $xmlfield = $xmlfieldset->addChild('field');
        $xmlfield->addAttribute('name', 'usergroup');
        $xmlfield->addAttribute('required', 'true');
        $xmlfield->addAttribute('type', 'sql');
        $xmlfield->addAttribute(
            'query',
            'SELECT id, title FROM #__usergroups WHERE id In (' . implode(',', $this->groups) . ')'
        );
        $xmlfield->addAttribute('key_field', 'id');
        $xmlfield->addAttribute('value_field', 'title');

        $xmlfield->addAttribute('label', 'PLG_USERGROUP_FIELD_GROUPS_USERS_LABEL');
        $xmlfield->addAttribute('description', 'PLG_USERGROUP_FIELD_GROUPS_USERS_DESCRIPTION');
        $form->setField($xmlform);

        return true;
    }

}
