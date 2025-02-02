<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Front end controller for OC user/admin auth in the app
 *
 * @package    OC
 * @category   Controller
 * @author     Chema <chema@open-classifieds.com>
 * @copyright  (c) 2009-2013 Open Classifieds Team
 * @license    GPL v3
 */

class Auth_Controller extends Controller
{

	/**
	 *
	 * Contruct that checks you are loged in before nothing else happens!
	 */
	function __construct(Request $request, Response $response)
	{
		// Assign the request to the controller
		$this->request = $request;

		// Assign a response to the controller
		$this->response = $response;


		//login control, don't do it for auth controller so we dont loop
		if ($this->request->controller()!='auth')
		{
			
			$url_bread = Route::url('oc-panel',array('controller'  => 'home'));
			Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Panel'))->set_url($url_bread));
				
			//check if user is login
			if (!Auth::instance()->logged_in( $request->controller(), $request->action(), $request->directory()))
			{
				Alert::set(Alert::ERROR, sprintf(__('You do not have permissions to access %s'), $request->controller().' '.$request->action()));
				$url = Route::get('oc-panel')->uri(array(
													 'controller' => 'auth', 
													 'action'     => 'login'));
				$this->redirect($url);
			}

            //in case we are loading another theme since we use the allow query we force the configs of the selected theme
            if (Theme::$theme != Core::config('appearance.theme') AND Core::config('appearance.allow_query_theme')=='1') 
                Theme::initialize(Core::config('appearance.theme'));

		}

		//the user was loged in and with the right permissions
        parent::__construct($request,$response);
		
		
	}


	/**
	 * Initialize properties before running the controller methods (actions),
	 * so they are available to our action.
	 * @param  string $template view to use as template
	 * @return void           
	 */
	public function before($template = NULL)
	{
        Theme::checker();
        
        $this->maintenance();
	
		if($this->auto_render===TRUE)
		{
            // Load the template
            $this->template = ($template===NULL)?'oc-panel/main':$template;
            //if its and ajx request I want only the content
            if(Core::get('rel')=='ajax')
                $this->template = 'oc-panel/content';
            $this->template = View::factory($this->template);
                
            // Initialize empty values
            $this->template->title            = __('Panel').' - '.core::config('general.site_name');
            $this->template->meta_keywords    = '';
            $this->template->meta_description = '';
            $this->template->meta_copyright   = 'Open Classifieds '.Core::VERSION;
            $this->template->header           = '';
            $this->template->content          = '';
            $this->template->footer           = '';
            $this->template->styles           = array();
            $this->template->scripts          = array();
            $this->template->user             = Auth::instance()->get_user();

            //non ajax request
            if (Core::get('rel')!='ajax')
            {
    			$this->template->header           = View::factory('oc-panel/header');
    			$this->template->footer           = View::factory('oc-panel/footer');

    			/**
    			 * custom options for the theme
    			 * @var array
    			 */
    			Theme::$options = Theme::get_options();
    			//we load earlier the theme since we need some info
    			Theme::load();

    			if (Theme::get('cdn_files') == FALSE)
    			{
    				//other color
    	            if (Theme::get('admin_theme')!='bootstrap' AND Theme::get('admin_theme')!='')
    	            {
    	                $theme_css = array('css/'.Theme::get('admin_theme').'-bootstrap.min.css' => 'screen',);
    	            }
    	            //default theme
    	            else
    	            {
    	                $theme_css = array('css/paper-bootstrap.min.css' => 'screen');
    	            }

                	$common_css = array('css/chosen.min.css' => 'screen',
                						'css/jquery.sceditor.default.theme.min.css' => 'screen',
                                        'css/loadingbar.css'=>'screen', 
                						'css/icon-picker.min.css'=>'screen', 
                						'css/font-awesome.min.css'=>'screen', 
                						'css/summernote.css'=>'screen', 
                						'css/bootstrap-tour.min.css'=>'screen',
                						'css/sweet-alert.min.css'=>'screen',
                                        'css/admin-styles.css?v='.Core::VERSION => 'screen');

                	Theme::$styles = array_merge($theme_css,$common_css);

    	            Theme::$scripts['footer'] = array('js/jquery.min.js',
    	            								  'js/jquery.cookie.min.js',	
    	            								  'js/iconPicker.min.js',
    	            								  'js/oc-panel/sidebar.js?v='.Core::VERSION,	
    												  'js/jquery.sceditor.bbcode.min.js',
    												  'js/summernote.min.js',
    												  'js/summernote-ext-video.js',
    												  'js/bootstrap.min.js', 
    											      'js/chosen.jquery.min.js',
    											      'js/bootstrap-tour.min.js',
    											      Route::url('jslocalization', array('controller'=>'jslocalization', 'action'=>'bstour')),
    											      'js/oc-panel/'.((Auth::instance()->get_user()->id_role!=Model_Role::ROLE_ADMIN) ? 'tour-user.js' : 'tour.js'),
    											      Route::url('jslocalization', array('controller'=>'jslocalization', 'action'=>'chosen')),
    											      'http://'.((Kohana::$environment!== Kohana::DEVELOPMENT)? 'market.'.Core::DOMAIN.'':'eshop.lo').'/embed.js',
    											      'js/sweet-alert.min.js',
    											      'js/favico.min.js',
    											      '//maps.google.com/maps/api/js?sensor=false&libraries=geometry&v=3.7',
    											      '//cdn.jsdelivr.net/gmaps/0.4.15/gmaps.min.js',
                                                      'js/jquery.bootgrid.min.js',
                                                      'js/query.bootgrid.fa.min.js',
                                                      'js/oc-panel/theme.init.js?v='.Core::VERSION,
                                                      );
    			}
    			else
    			{
    	            //other color
    	            if (Theme::get('admin_theme')!='bootstrap' AND Theme::get('admin_theme')!='')
    	            {
    	                $theme_css = array('//cdn.jsdelivr.net/bootswatch/3.3.6/'.Theme::get('admin_theme').'/bootstrap.min.css' => 'screen',);
    	            }
    	            //default theme
    	            else
    	            {
    	                $theme_css = array('//cdn.jsdelivr.net/bootswatch/3.3.6/paper/bootstrap.min.css' => 'screen');
    	            }

                	$common_css = array('//cdn.jsdelivr.net/chosen/1.0.0/chosen.css' => 'screen', 
                                        'css/jquery.sceditor.default.theme.min.css' => 'screen',
                                        'css/loadingbar.css'=>'screen', 
                                        'css/icon-picker.min.css'=>'screen', 
                                        '//cdn.jsdelivr.net/fontawesome/4.5.0/css/font-awesome.min.css'=>'screen', 
                                        '//cdn.jsdelivr.net/summernote/0.6.16/summernote.css'=>'screen', 
                                        '//cdn.jsdelivr.net/bootstrap-tour/0.10.1/css/bootstrap-tour.min.css'=>'screen',
                                        '//cdn.jsdelivr.net/sweetalert/1.1.3/sweetalert.css'=>'screen',
                                        'css/admin-styles.css?v='.Core::VERSION => 'screen');

                	Theme::$styles = array_merge($theme_css,$common_css);

    	            Theme::$scripts['footer'] = array('//cdn.jsdelivr.net/jquery/1.12.0/jquery.min.js',
    											      '//cdn.jsdelivr.net/jquery.cookie/1.4.1/jquery.cookie.min.js',
    	            								  'js/iconPicker.min.js',
    	            								  'js/oc-panel/sidebar.js?v='.Core::VERSION,	
    												  'js/jquery.sceditor.bbcode.min.js',
    											      '//cdn.jsdelivr.net/summernote/0.6.16/summernote.min.js',
    											      'js/summernote-ext-video.js',
    												  '//cdn.jsdelivr.net/bootstrap/3.3.6/js/bootstrap.min.js', 
    											      '//cdn.jsdelivr.net/chosen/1.0.0/chosen.jquery.min.js',
                                                      'js/bootstrap-tour.min.js',
    											      Route::url('jslocalization', array('controller'=>'jslocalization', 'action'=>'bstour')),
                                                      'js/oc-panel/'.(Auth::instance()->get_user()->id_role!=Model_Role::ROLE_ADMIN) ? 'tour-user.js' : 'tour.js',	
                                                      Route::url('jslocalization', array('controller'=>'jslocalization', 'action'=>'chosen')),
                                                      'js/oc-panel/sidebar.js?v='.Core::VERSION,	
                                                      '//cdn.jsdelivr.net/sweetalert/1.1.3/sweetalert.min.js',
                                                      'js/favico.min.js',
                                                      '//maps.google.com/maps/api/js?sensor=false&libraries=geometry&v=3.7',
                                                      '//cdn.jsdelivr.net/gmaps/0.4.15/gmaps.min.js',
                                                      '//'.((Kohana::$environment!== Kohana::DEVELOPMENT)? 'market.'.Core::DOMAIN.'':'eshop.lo').'/embed.js',
                                                      'js/jquery.bootgrid.min.js',
                                                      'js/query.bootgrid.fa.min.js',
                                                      'js/oc-panel/theme.init.js?v='.Core::VERSION,
                                                      );
    	        }
            }

		}
		
		
	}



    /**
     * Fill in default values for our properties before rendering the output.
     */
    public function after()
    {
        //ajax request
        if (Core::get('rel')=='ajax')
        {
            // Add defaults to template variables.
            $this->template->styles  = $this->template->styles;
            $this->template->scripts = array_reverse($this->template->scripts);
            $this->response->body($this->template->render());
        }
        else
            parent::after();
    }


}
