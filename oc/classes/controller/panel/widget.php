<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Widget
 *
 * @author      Chema <chema@garridodiaz.com>
 * @package     Core
 * @copyright   (c) 2009-2013 Open Classifieds Team
 * @license     GPL v3
 */

class Controller_Panel_Widget extends Auth_Controller {

    public function action_index()
    {
        $this->before('oc-panel/pages/widgets/main');

        //template header
        $this->template->title  = __('Widgets');

        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Widgets')));

        $this->template->scripts['footer'][] = 'js/jquery-sortable-min.js';
        $this->template->scripts['footer'][] = 'js/oc-panel/widgets.js';


        $this->template->widgets           = Widgets::get_widgets();
        $this->template->placeholders      = Widgets::get_placeholders();

    }
    

   	/**
   	 * action_save
   	 * @return save widget (make active)
   	 */
   	public function action_save()
   	{

        // save only changed values
        if($this->request->post())
        {
            //get place holder name
            $placeholder    = core::post('placeholder');
            //get widget class
            $widget         = core::post('widget_class');
            //widget name
            $widget_name    = core::post('widget_name');

            //$data = array();
            //extract all the data and prepare array
            foreach ($this->request->post() as $name=>$value) 
            {
                if ($name!='placeholder' AND $name!='widget_class' AND $name!='widget_name')
                    $data[$name] = $value;
            }

            $old_placeholder = NULL;

            $widget = new $widget();
            
            //the widget exists, we load it since we need the previous placeholder
            if ($widget_name!=NULL)
            {
                $widget->load($widget_name);
                $old_placeholder = $widget->placeholder;
            }

            $widget->placeholder = $placeholder;
            $widget->data = $data;


            try {

                $widget->save($old_placeholder);

                //clean cache config
                $c = new ConfigDB(); 
                $c->reload_config();

                if ($widget_name!=NULL)
                    Alert::set(Alert::SUCCESS,__('Widget '.$widget_name.' saved in '.$placeholder));
                else
                    Alert::set(Alert::SUCCESS,__('Widget created in '.$placeholder));

                $this->request->redirect(Route::url('oc-panel', array('controller'=>'widget', 'action'=>'index')));
            } catch (Exception $e) {
                //throw 500
                throw new HTTP_Exception_500();     
            }
        }
  
        
   	}

   	/**
   	 * action_remove
   	 * @return remove widget (deactivate)
   	 */
   	public function action_remove()
   	{
        $widget_name = $this->request->param('id');
        if ($widget_name!==NULL)
        {
            $w = Widget::factory($widget_name);

            if ($w->delete())
                Alert::set(Alert::SUCCESS,__('Widget '.$widget_name.' deleted'));
            else
                Alert::set(Alert::ERROR,__('Widget '.$widget_name.' can not be deleted'));
        }
        else
            Alert::set(Alert::ERROR,__('Widget param missing'));

        $this->request->redirect(Route::url('oc-panel', array('controller'=>'widget', 'action'=>'index')));
    }


    public function action_saveplaceholders()
    {
        $this->auto_render = FALSE;
        $this->template = View::factory('js');

        DB::delete('config')->where('group_name','=','placeholder')->execute();

        //for each placeholder
        foreach ($_GET as $placeholder => $widgets) 
        {
            if (!is_array($widgets))
            {
                $widgets = array($widgets);
                //d($widgets);
            }
                

            //insert in DB palceholders
            $confp = new Model_Config();
            $confp->group_name = 'placeholder';
            $confp->config_key = $placeholder;
            $confp->config_value = json_encode($widgets); 
            $confp->save();

            //edit each widget change placeholder
            foreach ($widgets as $wname) 
            {
                $w = Widget::factory($wname);
                if ($w!==NULL)
                {
                    if ($w->loaded)
                    {
                        $w->placeholder = $placeholder;
                        $w->save();
                    }
                }
               
            }
            
            
        }

        $this->template->content = __('Saved');

    }
}