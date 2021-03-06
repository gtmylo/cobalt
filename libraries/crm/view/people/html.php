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

class CobaltViewPeopleHtml extends JViewHtml
{
    function render()
    {
        $app = JFactory::getApplication();

        ///retrieve task list from model
        $model = new CobaltModelPeople();

        $state = $model->getState();
        
        //session data
        $session = JFactory::getSession();
        
        $user_id = CobaltHelperUsers::getUserId();
        $team_id = CobaltHelperUsers::getTeamId();
        $member_role = CobaltHelperUsers::getRole();
        
        $people_type_name = $session->get('people_type_filter');
        $user = $session->get('people_user_filter');
        $team = $session->get('people_team_filter');
        $stage = $session->get('people_stage_filter');
        $tag = $session->get('people_tag_filter');
        $status = $session->get('people_status_filter');
        
        //load java
        $document = & JFactory::getDocument();
        $document->addScript( JURI::base().'libraries/crm/media/js/people_manager.js' );
        
        //get list of people
        $people = $model->getPeople();
        $person = array();
        
        //Pagination
        $this->pagination = $model->getPagination();
        
        //determine if we are editing an existing person entry
        if( $app->input->get('id') ){
            //grab deal object
            $person = $people[0];
            if (is_null($person['id'])){
                $app->redirect(JRoute::_('index.php?view=people'),CRMText::_('COBALT_NOT_AUTHORIZED'));
            }
            $person['header'] = CRMText::_('COBALT_EDIT').' '.$person['first_name'] . ' ' . $person['last_name']; 
        }else{
            //else we are creating a new entry
            $person = array();
            $person['id'] = '';
            $person['first_name'] = "";
            $person['last_name'] = "";
            $person['company_id'] = ( $app->input->get('company_id') ) ? $app->input->get('company_id') : null;
            $person['deal_id'] = ( $app->input->get('deal_id') ) ? $app->input->get('deal_id') : null;
            
            //get company name to prefill data on page and hidden fields
            if ( $person['company_id'] ) {
                $company = CobaltHelperCompany::getCompany($person['company_id']);
                $person['company_name'] = $company[0]['name'];
                $person['company_id'] = $company[0]['id'];
            }
            
            //get deal name to prefill data on page and hidden fields
            if ( $person['deal_id'] ) {
                $deal = CobaltHelperDeal::getDeal($person['deal_id']);
                $person['deal_name'] = $deal[0]['name'];
                $person['deal_id'] = $deal[0]['id'];
            }
            
            $person['position'] = "";
            $person['phone'] = "";
            $person['email'] = "";
            $person['type'] = '';
            $person['source_id'] = null;
            $person['status_id'] = null;
            $person['header'] = CRMText::_('COBALT_PERSON_ADD'); 
        }

                //get total people associated with users account
                $total_people = CobaltHelperUsers::getPeopleCount($user_id,$team_id,$member_role);
                
                //get filter types
                $people_types = CobaltHelperPeople::getPeopleTypes();
                $people_type_name = ( $people_type_name && array_key_exists($people_type_name,$people_types) ) ? $people_types[$people_type_name] : $people_types['all'];
                
                //get column filters
                $column_filters = CobaltHelperPeople::getColumnFilters();
                $selected_columns = CobaltHelperPeople::getSelectedColumnFilters();
                
                //get user filter
                //get associated users//teams
                $teams = CobaltHelperUsers::getTeams();
                $users = CobaltHelperUsers::getUsers();
                
                if ( $user AND $user != $user_id AND $user != 'all' ){
                    $user_info = CobaltHelperUsers::getUsers($user);
                    $user_info = $user_info[0];
                    $user_name = $user_info['first_name'] . " " . $user_info['last_name'];
                }else if ( $team ){
                    $team_info = CobaltHelperUsers::getTeams($team);
                    $team_info = $team_info[0];
                    $user_name = $team_info['team_name'].CRMText::_('COBALT_TEAM_APPEND');
                }else if ( $user == 'all' ) {
                    $user_name = CRMText::_('COBALT_ALL_USERS');
                }else{
                    $user_name = CRMText::_('COBALT_ME');            
                }
                
                //get stage filter
                $stages = CobaltHelperPeople::getStages();
                $stages_name = ( $stage ) ? $stages[$stage] : $stages['past_thirty'];
                
                //get tag filter
                $tag_list = CobaltHelperPeople::getTagList();
                for ( $i=0; $i<count($tag_list); $i++ ){
                    if ( $tag_list[$i]['id'] == $tag AND $tag != 'any' ){
                        $tag_name = $tag_list[$i]['name'];
                        break;
                    }
                }
                $tag_name = ( $tag AND $tag != 'any' ) ? $tag_name : 'all tags';
                
                //get status filter
                $status_list = CobaltHelperPeople::getStatusList();
                for ( $i=0; $i<count($status_list); $i++ ){
                    if ( $status_list[$i]['id'] == $status AND $status != 'any' ){
                        $status_name = $status_list[$i]['name'];
                        break;
                    }
                }
                $status_name = ( $status AND $status != 'any' ) ? $status_name : 'any status';

                $dropdowns = $model->getDropdowns();

                //Load Events & Tasks for person
                $layout = $this->getLayout();
                if ( $layout == "person" ){
                        $model = new CobaltModelEvent();
                        $events = $model->getEvents("person",null,$app->input->get('id'));
                        $this->event_dock = CobaltHelperView::getView('events','event_dock','phtml',array('events'=>$events));
                        $this->deal_dock = CobaltHelperView::getView('deals','deal_dock','phtml', array('deals'=>$person['deals']));

                        $this->document_list = CobaltHelperView::getView('documents','document_row','phtml', array('documents'=>$person['documents']));
                        $this->custom_fields_view = CobaltHelperView::getView('custom','default','phtml',array('type'=>'people','item'=>$person));

                        $this->acymailing = CobaltHelperConfig::checkAcymailing();

                        if ( $this->acymailing ){
                            $mailing_list = new CobaltHelperMailinglists();
                            $mailing_lists = $mailing_list->getMailingLists();
                            $newsletters = array();
                            if ( is_array($mailing_lists) && array_key_exists(0,$mailing_lists) ) { 
                                $newsletters = $mailing_list->getNewsletters($mailing_lists[0]->listid);
                            }
                            $this->acymailing_dock = CobaltHelperView::getView('acymailing','default','phtml',array('newsletters'=>$newsletters,'mailing_lists'=>$mailing_lists));
                        }

                        if ( CobaltHelperBanter::hasBanter() ){
                            $room_list = new CobaltHelperTranscriptlists();
                            $room_lists = $room_list->getRooms();
                            $transcripts = array();
                            if ( is_array($room_lists) && count($room_lists) > 0 ) { 
                                $transcripts = $room_list->getTranscripts($room_lists[0]->id);
                            }
                            $this->banter_dock = CobaltHelperView::getView('banter','default','phtml',array('rooms'=>$room_lists,'transcripts'=>$transcripts));
                        }
                }

                if ( $layout == "default" ){
                    $total = $model->getTotal();
                    $pagination = $model->getPagination();
                    $this->people_list = CobaltHelperView::getView('people','list','phtml',array('people'=>$people,'total'=>$total,'pagination'=>$pagination));
                    $this->people_filter = $state->get('Deal.people_name');
                }

                if ( $layout == "edit" ){
                    $item = $app->input->get('id') && array_key_exists(0,$people) ? $people[0] : array('id'=>'');
                    $this->edit_custom_fields_view = CobaltHelperView::getView('custom','edit','phtml',array('type'=>'people','item'=>$item));

                    $companyModel = new CobaltModelCompany();

                    $json = TRUE;
                    $companyNames = $companyModel->getCompanyNames($json);
                    $document->addScriptDeclaration("var company_names=".$companyNames.";");
                }

                if ( CobaltHelperTemplate::isMobile() && $app->input->get('id')){
                    $this->add_note = CobaltHelperView::getView('note','edit','phtml',array('add_note'=>$add_note));

                    $this->add_task = CobaltHelperView::getView('events','edit_task','phtml',array('association_type'=>'person','assocation_id'=>$app->input->get('id')));
                }

        
        //assign results to view
        $this->people = $people;
        $this->person = $person;
        $this->totalPeople = $total_people;
        $this->people_type_name = $people_type_name;
        $this->people_types = $people_types;
        $this->user_id = $user_id;
        $this->team_id = $team_id;
        $this->member_role = $member_role;
        $this->user_name = $user_name;
        $this->teams = $teams;
        $this->users = $users;
        $this->stages = $stages;
        $this->stages_name = $stages_name;
        $this->tag_list = $tag_list;
        $this->tag_name = $tag_name;
        $this->status_list = $status_list;
        $this->status_name = $status_name;
        $this->state = $state;
        $this->column_filters = $column_filters;
        $this->selected_columns = $selected_columns;
        $this->dropdown = $dropdowns;

        //display
        return parent::render();
    }
    
}
?>
