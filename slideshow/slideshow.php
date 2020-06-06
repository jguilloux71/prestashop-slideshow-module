<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Zido <jguilloux@gmail.com>
 *  @copyright 2019-2020 Zido
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  https://github.com/jguilloux71/
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


class Slideshow extends Module {

    public function __construct() {
        $this->name = 'slideshow';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Zido';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Basic but beautiful slideshow for your homepage');
        $this->description = $this->l('Add a slideshow for your homepage');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        // array(label, default value, html renderer authorized, desc, unit)
        $this->options = array(
            'SLIDESHOW_MAX_WIDTH'    => array($this->l('Maximal width of images'),     535, false, $this->l(''),                                             'pixels'),
            'SLIDESHOW_MAX_HEIGHT'   => array($this->l('Maximal height of images'),    300, false, $this->l(''),                                             'pixels'),
            'SLIDESHOW_DISPLAY_TIME' => array($this->l('Display time of each image'), 3000, false, $this->l('Time during which an image remains displayed'), 'milliseconds')
        );

        $this->_checkNeedConfiguration();
    }


    private function _checkNeedConfiguration() {
        if (!Configuration::get('SLIDESHOW_MAX_WIDTH')) {
            $this->warning = $this->l('Slideshow module need to be configured');
        }
    }
    

    public function install() {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->_updateAllDefaultValues();
    }
    
    
    private function _updateAllDefaultValues() {
        $no_err = true;
        foreach ($this->options as $key => $value) {
            Configuration::updateValue($key, $value[1], $value[2]) || $no_err = false;
        }
        return $no_err;
    }


    public function uninstall() {
        return parent::uninstall()
            && $this->_deleteAllValues();
    }


    private function _deleteAllValues() {
        $no_err = true;
        foreach ($this->options as $key => $value) {
            Configuration::deleteByName($key) || $no_err = false;
        }
        return $no_err;
    }


    public function hookDisplayHome() {
        // Specific CSS for 'slideshow', in <HEAD> tag
        $this->context->controller->addCSS($this->_path . 'views/css/slideshow.css', 'all');

        // JS for slideshow, in <HEAD> tag
        $this->context->controller->addJS($this->_path . 'views/js/slideshow.js', 'all');

        // For translations in template files
        $this->context->smarty->assign(
            array(
                'slideshow_max_width'    => Configuration::get('SLIDESHOW_MAX_WIDTH'),
                'slideshow_max_height'   => Configuration::get('SLIDESHOW_MAX_HEIGHT'),
                'slideshow_display_time' => Configuration::get('SLIDESHOW_DISPLAY_TIME')
            )
        );
        return $this->display(__FILE__, 'slideshow.tpl');
    }


    public function getContent() {
        $output = null;
        $errors = 0;
        $form_fields = array();      // to store values of fields from module form
 
        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($this->options as $key => $value) {
                $form_fields = array_merge($form_fields, array($key => strval(Tools::getValue($key))));
            }

            if (empty($form_fields[SLIDESHOW_MAX_WIDTH])) {
                $errors += 1;
                $output .= $this->displayError( $this->l('Invalid width'));
            }

            if (empty($form_fields[SLIDESHOW_MAX_HEIGHT])) {
                $errors += 1;
                $output .= $this->displayError( $this->l('Invalid height') );
            }

            if (empty($form_fields[SLIDESHOW_DISPLAY_TIME])) {
                $errors += 1;
                $output .= $this->displayError( $this->l('Invalid display time') );
            }

            if ($errors == 0) {
                // Update values in database
                if (!$this->_updateCurrentValues($form_fields)) {
                    $output .= $this->displayConfirmation($this->l('Settings: error during update of values in database!'));
                }
                else {
                    $output .= $this->displayConfirmation($this->l('Settings updated'));
                }
            }
            elseif ($errors == 1) {
                $output .= $this->displayError('1 ' . $this->l('error found'));
            }
            else {
                $output .= $this->displayError($errors . ' ' . $this->l('errors found'));
            }
        }

        return $output . $this->displayForm();
    }


    private function _updateCurrentValues($current) {
        $no_err = true;
        foreach ($this->options as $key => $value) {
            Configuration::updateValue($key, $current[$key], $value[2]) || $no_err = false;
        }
        return $no_err;
    }


    public function displayForm() {
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon'  => 'icon-cogs'
            ),
            'tabs' => array(
                'main_settings'   => $this->l('Main settings'),
                'slides_settings' => $this->l('Slides settings')
            ),
            'input' => array(
                array(
                    'type'     => 'text',
                    'label'    => $this->options['SLIDESHOW_MAX_WIDTH'][0],
                    'name'     => 'SLIDESHOW_MAX_WIDTH',
                    'size'     => 100,
                    'required' => true,
                    'desc'     => $this->options['SLIDESHOW_MAX_WIDTH'][3],
                    'suffix'   => $this->options['SLIDESHOW_MAX_WIDTH'][4],
                    'tab'      => 'main_settings'
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->options['SLIDESHOW_MAX_HEIGHT'][0],
                    'name'     => 'SLIDESHOW_MAX_HEIGHT',
                    'size'     => 100,
                    'required' => true,
                    'desc'     => $this->options['SLIDESHOW_MAX_HEIGHT'][3],
                    'suffix'   => $this->options['SLIDESHOW_MAX_HEIGHT'][4],
                    'tab'      => 'main_settings'
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->options['SLIDESHOW_DISPLAY_TIME'][0],
                    'name'     => 'SLIDESHOW_DISPLAY_TIME',
                    'size'     => 100,
                    'required' => true,
                    'desc'     => $this->options['SLIDESHOW_DISPLAY_TIME'][3],
                    'suffix'   => $this->options['SLIDESHOW_DISPLAY_TIME'][4],
                    'tab'      => 'main_settings'
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button btn btn-default pull-right'
            )
        );
     
        return $this->_helperForm($fields_form);
    }


    private function _helperForm($form) {
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
     
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
     
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex .
                            '&configure=' . $this->name .
                            '&save' . $this->name .
                            '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex .
                            '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        foreach ($this->options as $key => $value) {
            $helper->fields_value[$key] = Configuration::get($key);
        }

        return $helper->generateForm($form);
    }
}
?>
