<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class CobaltViewBrandingHtml extends JViewHtml
{

    function render($tpl = null)
    {
        //authenticate the current user to make sure they are an admin
        CobaltHelperUsers::authenticateAdmin();

        //menu Links
        $menu = CobaltHelperMenu::getMenuModules();
        $this->menu = $menu;
        
         //add javascript
        $document =& JFactory::getDocument();
        $document->addScript(JURI::base().'libraries/crm/media/js/branding_manager.js');
        $document->addScript(JURI::base().'libraries/crm/media/js/cobalt-admin.js');
        
        //stylesheets
        CobaltHelperStyles::loadStyleSheets();
        
        //view refs
        $model = new CobaltModelBranding();
        $themes = $model->getThemes();

        //toolbar buttons
        CRMToolbarHelper::save('save','Save');

        //toolbar items
        $list = array( 
            'dashboard',
            'deals',
            'people',
            'companies',
            'calendar',
            'documents',
            'goals',
            'reports'
        );
        $this->toolbar_list = $list;
        $this->themes = $themes;
        $this->site_logo = CobaltHelperStyles::getSiteLogo();
        $this->site_name = CobaltHelperStyles::getSiteName();

        //assign default theme
        foreach ( $this->themes as $key=>$row ){
                if ( $row['assigned'] )
                 $document->addScriptDeclaration("var assigned_theme=".$row['id'].";");
         }
        
        //display
        return parent::render();
    }
}