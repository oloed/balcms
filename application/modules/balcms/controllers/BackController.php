<?php
require_once 'Zend/Controller/Action.php';
class Balcms_BackController extends Zend_Controller_Action {

	# ========================
	# VARIABLES
	
	const MODULE = 'Balcms';
	
	
	# ========================
	# CONSTRUCTORS
	
	/**
	 * Initialise
	 * @return
	 */
	public function init ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# Layout
		$App->setArea('back');
		
		# Login
		$App->setOption('logged_in_forward',  array(array('action'=>'dashboard'),'back',true))
			->setOption('logged_out_forward', array(array('action'=>'login'),'back',true))
			;
		
		# Authenticate / redirect to login if need be
		if ( !in_array($this->getRequest()->getActionName(), array(false, 'login', 'index')) ) {
			# Within unsafe area, must authenticate
			$App->authenticate(true, false);
		}
		
		# Check Permission
		try {
			if ( $App->hasIdentity() && !$App->hasPermission('permission-admin') ) {
				# Log
				$Log = Bal_App::getLog();
				$log_details = array();
				$Log->log(array('log-admin-permission',$log_details),Bal_Log::ERR,array('friendly'=>true,'details'=>$log_details));
				
				# Logout
				//$App->logout(true);
			}
		}
		catch ( Exception $Exception ) {
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Navigation
		$App->applyNavigation();
		
		# Done
		return true;
	}
	
	
	# ========================
	# INDEX
	

	public function indexAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# Redirect
		return $App->authenticate(true, true);
	}

	/**
	 * Logout the User and redirect
	 * @return bool
	 */
	public function logoutAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# Logout
		$App->logout(true);
		
		# Done
		return true;
	}

	/**
	 * Login the User and redirect
	 * @return bool
	 */
	public function loginAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		
		# Load
		$login = $Request->getParam('login', array());
		array_keys_keep_ensure($login, array('username','password','locale','remember'));
		
		# Check
		if ( !empty($login['username']) && !empty($login['password']) ) {
			# Login
			try {
				# Prepare Login
				$username = $login['username'];
				$password = $login['password'];
				$locale = $login['locale'];
				$remember = $login['remember'];
				
				# Login and Forward
				return $App->loginForward($username, $password, $locale, $remember, false, true);
			}
			catch ( Exception $Exception ) {
				# Log the Event and Continue
				$Exceptor = new Bal_Exceptor($Exception);
				$Exceptor->log();
			}
		}
		
		# Render
		$App->setArea('back')->setLayout('login');
		$this->render('index/login');
		
		# Done
		return true;
	}

	public function dashboardAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->activateNavigationItem('back.main', 'dashboard', true);
		
		# Render
		$this->render('index/dashboard');
		
		# Done
		return true;
	}


	# ========================
	# CRUD
	

	public function crudAction ( ) {
		# Redirect
		return $this->_forward('crud-list');
	}

	public function crudListAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		$ItemList = array();
		
		# Prepare Type
		$type = $Request->getParam('type');
		$typeLower = strtolower($type);
		$Table = Bal_Form_Doctrine::getTable($type);
		$tableName = Bal_Form_Doctrine::getTableName($type);
		$labelColumnName = Bal_Form_Doctrine::getTableLabelColumnName($tableName);
		
		# Menu
		$App->activateNavigationItem('back.main', 'crud-list-'.$typeLower, true);
		
		
		# Search
		$search = $App->fetchSearch();
		$searchQuery = delve($search,'query');
		$this->view->search = $search;
		
		# Prepare
		$ListQuery = $Table->createQuery()
			->select('*')
			->orderBy($labelColumnName.' ASC')
			->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		# Handle
		if ( $searchQuery ) {
			// Search
			if ( method_exists($Table,'search') ) {
				$Query = $Table->search($searchQuery, $ListQuery);
			} else {
				$Query = $ListQuery->andWhere($labelColumnName.' LIKE ?', '%'.$searchQuery.'%');
			}
			$ItemList = $Query->execute();
		} else {
			// No Search
			$ItemList = $ListQuery->execute();
		}
		
		# Apply
		$this->view->ItemList = $ItemList;
		$this->view->type = $type;
		
		
		# Render
		$this->render('crud/crud-list');
		
		# Done
		return true;
	}
	
	public function crudEditAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		$Item = array();
		
		# Prepare Type
		$type = $Request->getParam('type');
		$typeLower = strtolower($type);
		$Table = Bal_Form_Doctrine::getTable($type);
		$tableName = Bal_Form_Doctrine::getTableName($type);
		$labelColumnName = Bal_Form_Doctrine::getTableLabelColumnName($tableName);
		
		# Fetch
		$Item = $this->_saveItem($type);
		
		# Menu
		$App->activateNavigationItem('back.main', 'crud-'.($Item->id?'list':'new').'-'.$typeLower, true);
		
		
		# Form
		$Form = Bal_Form_Doctrine::fetchForm($tableName,$Item);
		$Form
			->setAction('')
			->setMethod('post')
			->addElement('submit', 'submit',array('class'=>'button-primary','label'=>'Save Changes'));
		
		# Apply
		$this->view->Item = $Item;
		$this->view->type = $type;
		$this->view->Form = $Form;
		
		
		# Render
		$this->render('crud/crud-edit');
		
		# Done
		return true;
	}
	
	public function crudNewAction ( ) {
		# Redirect
		return $this->_forward('crud-edit');
	}
	
	public function crudDeleteAction ( ) {
		# Prepare
		$type = $Request->getParam('type');
		
		# Delete
		$this->_deleteItem($type);
		
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action'=>'item-list','type'=>$type), 'back', true);
	}
	
	
	# ========================
	# USER
	

	public function userAction ( ) {
		# Redirect
		return $this->_forward('user-list');
	}

	public function userListAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->activateNavigationItem('back.main', 'user-list', true);
		$UserList = array();
		
		# Search
		$search = $App->fetchSearch();
		$searchQuery = delve($search,'query');
		$this->view->search = $search;
		
		# Prepare
		$ListQuery = Doctrine_Query::create()->select('u.id, u.displayname, u.username, u.created_at, u.email, u.type, u.status, u.created_at, ua.*')->from('User u, u.Avatar ua')->orderBy('u.username ASC')->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		# Handle
		if ( $searchQuery ) {
			// Search
			$Query = Doctrine::getTable('User')->search($searchQuery, $ListQuery);
			$UserList = $Query->execute();
		} else {
			// No Search
			$UserList = $ListQuery->execute();
		}
		
		# Apply
		$this->view->UserList = $UserList;
		
		# Render
		$this->render('user/user-list');
		
		# Done
		return true;
	}
	
	public function userEditAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# Fetch
		$type = 'user';
		$User = $this->_saveUser();
		$App->activateNavigationItem('back.main', 'user-'.($User->id ? 'list' : 'new'), true);
		
		# Form
		$Form = Bal_Form_Doctrine::fetchForm('User',$User);
		$Form
			->setAction('')
			->setMethod('post')
			->addElement('submit', 'submit',array('class'=>'button-primary','label'=>'Save Changes'));
		
		# Apply
		$this->view->User = $User;
		$this->view->type = $type;
		$this->view->Form = $Form;
		
		# Render
		$this->render('user/user-edit');
		
		# Done
		return true;
	}
	
	public function userNewAction ( ) {
		# Redirect
		return $this->_forward('user-edit');
	}
	
	
	public function userDeleteAction ( ) {
		# Delete
		$this->_deleteItem('user');
		
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action'=>'user-list'), 'back', true);
	}
	
	
	# ========================
	# SUBSCRIPTION
	

	public function subscriptionAction ( ) {
		# Redirect
		return $this->_forward('subscriber-list');
	}

	public function subscriberListAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->activateNavigationItem('back.main', 'subscriber-list', true);
		$SubscriberList = array();
		
		# Search
		$search = $App->fetchSearch();
		$searchQuery = delve($search,'query');
		$this->view->search = $search;
		
		# Prepare
		$ListQuery = Doctrine_Query::create()
			->select('s.id, s.email, s.displayname, s.subscriptions, st.name, s.status, s.created_at, COUNT(sMessagePublished.id) as subscription_published_count')
			->from('User s, s.SubscriptionTags st')
			->where('s.status = ?', 'published')
			->andWhere('s.subscriptions != ?', '')
			->orderBy('s.email ASC')
			->leftJoin('s.ReceivedMessages sMessagePublished WITH sMessagePublished.template = ? AND sMessagePublished.status = ?', array('content-subscription','published'))
			->groupBy('s.id')
			->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		# Handle
		if ( $searchQuery ) {
			// Search
			$Query = Doctrine::getTable('Subscriber')->search($searchQuery, $ListQuery);
			$SubscriberList = $Query->execute();
		} else {
			// No Search
			$SubscriberList = $ListQuery->execute();
		}
		
		# Apply
		$this->view->SubscriberList = $SubscriberList;
		
		# Render
		$this->render('user/subscriber-list');
		
		# Done
		return true;
	}

	# ========================
	# MEDIA
	

	public function mediaAction ( ) {
		# Redirect
		return $this->_forward('media-list');
	}
	
	public function mediaDeleteAction ( ) {
		# Delete
		$this->_deleteItem('media');
		
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action'=>'media-list'), 'back', true);
	}
	
	public function mediaEditAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->activateNavigationItem('back.main', 'media-list', true);
		
		# Prepare
		$Media = array();
		
		# Save
		try {
			$Media = $this->_saveMedia();
			if ( !$Media->id ) {
				# No Media
				return $this->_redirect('media-new');
			}
			elseif ( !delve('media.id') && $Media->id ) {
				# New Media
				return $this->getHelper('redirector')->gotoRoute(array('action' => 'media-edit', 'media' => $Media->code), 'back', true);
			}
		}
		catch ( Exception $Exception ) {
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Apply
		$this->view->Media = $Media->toArray();
		
		# Render
		$this->render('media/media-edit');
		
		# Done
		return true;
	}
	
	public function mediaListAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->activateNavigationItem('back.main', 'media-list', true);
		$MediaList = array();
		
		# Search
		$search = $App->fetchSearch();
		$searchQuery = delve($search,'query');
		$this->view->search = $search;
		
		# Save
		try {
			$Media = $this->_saveMedia();
			if ( is_object($Media) )
			$this->view->Media = $Media->toArray();
		}
		catch ( Exception $Exception ) {
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Prepare
		$ListQuery = Doctrine_Query::create()->select('m.*, ma.*')->from('Media m, m.Author')->orderBy('m.code ASC')->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		# Handle
		if ( $searchQuery ) {
			// Search
			$Query = Doctrine::getTable('Media')->search($searchQuery, $ListQuery);
			$MediaList = $Query->execute();
		} else {
			// No Search
			$MediaList = $ListQuery->execute();
		}
		
		# Apply
		$this->view->MediaList = $MediaList;
		
		# Render
		$this->render('media/media-list');
		
		# Done
		return true;
	}
	
	# ========================
	# CONTENT
	
	
	public function contentAction ( ) {
		# Redirect
		return $this->_forward('content-list');
	}

	public function contentDeleteAction ( ) {
		# Delete
		$Content = $this->_deleteItem('content');
		$content = delve($Content,'Parent.code');
		
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action'=>'content-list','content'=>$content), 'back', true);
	}
	
	public function getContentList ( ) {
		# Fetch
		$ContentListQuery = Doctrine_Query::create()->select('c.title, c.id, c.parent_id, c.position, cr.path')->from('Content c, c.Route cr')->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		$ContentList = $ContentListQuery->execute();
		$ContentList = array_tree_flat($ContentList, 'id', 'parent_id', 'level', 'position');
		
		# Done
		return $ContentList;
	}

	public function contentEditAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Content = $ContentCrumbs = array();
		
		# Save
		$Content = $this->_saveContent();
		if ( !$Content->id ) {
			return $this->_forward('content-new');
		}
		$type = $Content->type;
		
		# Menu
		$App->activateNavigationItem('back.main', $type.'-list', true);
		
		# Fetch
		$ContentArray = $Content->toArray();
		$ContentCrumbs[] = $ContentArray;
		
		# Fetch content for use in dropdown
		$ContentList = $this->getContentList();
		
		# Apply
		$this->view->type = $type;
		$this->view->ContentCrumbs = $ContentCrumbs;
		$this->view->ContentList = $ContentList;
		$this->view->Content = $ContentArray;
		
		# Render
		$this->render('content/content-edit');
		
		# Done
		return true;
	}

	public function contentNewAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$type = $this->_getParam('type', 'content');
		$App->activateNavigationItem('back.main', $type.'-new', true);
		$Content = $ContentCrumbs = array();
		
		# Save/Load
		try {
			$Content = $this->_saveContent();
			if ( $Content->id ) {
				return $this->getHelper('redirector')->gotoRoute(array('action' => 'content-edit', 'content' => $Content->code), 'back', true);
			}
		}
		catch ( Exception $Exception ) {
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Prepare New Content
		$Content->published_at = doctrine_timestamp();
		if ( $type === 'event' ) {
			$Content->event_start_at = doctrine_timestamp();
			$Content->event_finish_at = doctrine_timestamp();
		}
		
		# Fetch
		$ContentArray = $Content->toArray();
		$ContentCrumbs[] = $ContentArray;
		
		# Fetch content for use in dropdown
		$ContentList = $this->getContentList();
		
		# Apply
		$this->view->type = $type;
		$this->view->ContentCrumbs = $ContentCrumbs;
		$this->view->ContentList = $ContentList;
		$this->view->Content = $ContentArray;
		
		# Render
		$this->render('content/content-edit');
		
		# Done
		return true;
	}

	public function contentListAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$type = $this->_getParam('type', 'content');
		$App->activateNavigationItem('back.main', $type.'-list', true);
		$Content = $ContentCrumbs = $ContentList = $ContentArray = array();
		
		# Search
		$search = $App->fetchSearch();
		$searchQuery = delve($search,'query');
		$this->view->search = $search;
		
		# Param
		$content = fetch_param('content');
		
		# Prepare
		$ListQuery = Doctrine_Query::create()
			->select('c.*, cr.*, ct.*, ca.*, cp.*, cm.*')
			->from('Content c, c.Route cr, c.Tags ct, c.Author ca, c.Parent cp, c.Avatar cm')
			->where('c.status = ?', 'published')
			->orderBy('c.position ASC, c.id ASC')
			->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		if ( $type !== 'content' ) {
			$ListQuery->andWhere('c.type = ?', $type);
		}
		
		# Handle
		if ( $searchQuery ) {
			// Search
			$Query = Doctrine::getTable('Content')->search($searchQuery, $ListQuery);
			$ContentList = $Query->execute();
		} else {
			// No Search
			
			# Fetch Crumbs
			$Content = $this->_getContent(false);
			if ( $Content ) {
				// We have a content as a root
				$ContentArray = $Content->toArray();
				$ContentCrumbs = $Content->getCrumbs(Doctrine::HYDRATE_ARRAY, true);
			}
			
			# Fetch list
			if ( $Content ) {
				// Children
				$ContentList = $ListQuery->andWhere('cp.id = ?', $Content->id)->execute();
			} else {
				// Roots
				if ( $type === 'content' )
					$ContentList = $ListQuery->andWhere('NOT EXISTS (SELECT cpc.id FROM Content cpc WHERE cpc.id = c.parent_id)')->execute();
				else
					$ContentList = $ListQuery->execute();
			}
			
			// If nothing, use us
			if ( !$ContentList && $Content ) {
				$ContentList = array($Content);
			}
		
		}
		
		# Apply
		$this->view->type = $type;
		$this->view->ContentCrumbs = $ContentCrumbs;
		$this->view->ContentList = $ContentList;
		$this->view->Content = $ContentArray;
		
		# Render
		$this->render('content/content-list');
		
		# Done
		return true;
	}

	public function contentPositionAction ( ) {
		# Prepare
		$Request = $this->getRequest();
		$json = json_decode($Request->getPost('json'), true);
		$positions = $json['positions'];
		
		# Handle
		try {
			$data = array('success' => false);
			if ( !empty($positions) ) {
				foreach ( $positions as $id => $position ) {
					$Content = Doctrine::getTable('Content')->find($id);
					$Content->position = $position;
					$Content->save();
				}
				$data = array('success' => true);
			}
		}
		catch ( Exception $Exception ) {
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Respond
		$this->getHelper('json')->sendJson($data);
	}

	# ========================
	# CONTENT: GENERIC
	
	protected function _getContent ( $create = true ) {
		# Prepare
		$App = $this->getHelper('App');
		$Query = Doctrine_Query::create()->select('i.*, ir.*, it.*, ia.*, ip.*, im.*')->from('Content i, i.Route ir, i.Tags it, i.Author ia, i.Parent ip, i.Avatar im');
		
		# Fetch
		$Content = $App->fetchItem('Content', $Query, $create);
		
		# Return Content
		return $Content;
	}
	
	protected function _saveContent ( ) {
		# Prepare
		$Content = $this->_getContent();
		$Connection = Bal_App::getDataConnection();
		$Request = $this->getRequest();
		$Log = Bal_App::getLog();
		
		try {
			# Fetch
			$content = fetch_param('content');
			
			# Check Existance of Save
			if ( empty($content) || is_string($content) ) {
				# Return Found/New Content
				return $Content;
			}
			
			# Start
			$Connection->beginTransaction();
			
			# Fetch
			$Avatar = delve($content,'Avatar');
			
			# Prepare
			array_keys_keep_ensure($content, array('code', 'content', 'description', 'parent', 'status', 'tags', 'title', 'type'));
			
			# Tags
			$tags = prepare_csv_str($content['tags']);
			unset($content['tags']);
			
			# Parent
			$parent = $content['parent'];
			unset($content['parent']);
			if ( $parent ) {
				$Content->Parent = Doctrine::getTable('Content')->find($parent);
			} else {
				$Content->Parent = null;
			}
			
			# Apply
			$Content->merge($content);
			
			# Pre Save
			if ( !$Content->id )
				$Content->save();
			
			# Avatar
			if ( $Avatar )
				$Content->Avatar = $Avatar;
			
			# Tags
			$Content->Tags = $tags;
		
			# Post Save
			$Content->save();
		
			# Stop Duplicates
			$Request->setPost('content', $Content->code);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				'Content'		=> $Content->toArray(),
				'contentUrl'	=> $this->view->url()->content($Content)->toString()
			);
			$Log->log(array('log-content-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			$Connection->rollback();
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Done
		return $Content;
	}
	
	# ========================
	# MEDIA: GENERIC
	
	protected function _getMedia ( $create = true ) {
		# Prepare
		$App = $this->getHelper('App');
		$Query = Doctrine_Query::create()->select('i.*, ia.*')->from('Media i, i.Author ma');
		
		# Fetch
		$Media = $App->fetchItem('Media', $Query, $create);
		
		# Return Media
		return $Media;
	}
	
	protected function _saveMedia ( $param = 'media' ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Media = $this->_getMedia();
		$Log = Bal_App::getLog();
		
		# Handle
		try {
			# Fetch
			$Request = $this->_request;
			$media = fetch_param($param);
			$file = delve($media,'file');
		
			# Check
			if ( empty($file) || empty($file['name']) ) {
				return $Media;
			}
			
			# Start
			$Connection->beginTransaction();
			
			# Prepare
			array_keys_keep($media, array('code', 'title', 'path', 'size', 'type', 'mimetype', 'width', 'height'));
		
			# Apply
			$Media->merge($media);
			$Media->file = $file;
			$Media->save();
		
			# Stop Duplicates
			$Request->setPost($param, $Media->code);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				'Media'		=> $Media->toArray(),
				'mediaUrl'	=> $this->view->url()->media($Media)->toString()
			);
			$Log->log(array('log-media-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			# Revert
			$Connection->rollback();
			
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Done
		return $Media;
	}
	
	# ========================
	# USER: GENERIC
	
	protected function _getUser ( $create = true ) {
		# Prepare
		$App = $this->getHelper('App');
		$Query = null;
		
		# Fetch
		$User = $App->fetchItem('User', $Query, $create);
		
		# Return User
		return $User;
	}
	
	protected function _saveUser ( ) {
		# Prepare
		$User = $this->_getUser();
		$Connection = Bal_App::getDataConnection();
		$Request = $this->getRequest();
		$Log = Bal_App::getLog();
		
		try {
			# Fetch
			$user = fetch_param('user');
			
			# Check Existance of Save
			if ( empty($user) || is_string($user) ) {
				# Return Found/New Content
				return $User;
			}
			
			# Start
			$Connection->beginTransaction();
			
			# Fetch
			$Avatar = delve($user,'Avatar');
			
			# Prepare
			array_keys_unset($user, array('Avatar'));
			
			# Apply
			$User->merge($user);
			
			# Pre Save
			if ( !$User->id )
				$User->save();
			
			# Avatar
			if ( $Avatar !== null )
				$User->Avatar = $Avatar;
			
			# Post Save
			$User->save();
			
			# Stop Duplicates
			$Request->setPost('user', $User->code);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				'User'			=> $User->toArray(),
				'userUrl'		=> $this->view->url()->user($User)->toString()
			);
			$Log->log(array('log-user-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			$Connection->rollback();
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Done
		return $User;
	}
	
	
	# ========================
	# ITEM: GENERIC
	
	protected function _getItem ( $type, $create = true ) {
		# Prepare
		$App = $this->getHelper('App');
		$Query = null;
		
		# Fetch
		$Item = $App->fetchItem($type, $Query, $create);
		
		# Return Item
		return $Item;
	}
	
	protected function _saveItem ( $type ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Request = $this->getRequest();
		$Log = Bal_App::getLog();
		
		# Fetch
		$Item = $this->_getItem($type);
		
		# Handle
		try {
			# Fetch
			$item = fetch_param($type);
			
			# Check Existance of Save
			if ( empty($item) || is_string($item) ) {
				# Return Found/New Content
				return $Item;
			}
			
			# Start
			$Connection->beginTransaction();
			
			# Prepare
			// array_keys_keep_ensure($user, array('username', 'firstname', 'lastname', 'parent', 'status', 'tags', 'title', 'type'));
			
			# Apply
			$Item->merge($item);
			
			# Save
			$Item->save();
			
			# Stop Duplicates
			$Request->setPost('item', $Item->id);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				'Item'			=> $Item->toArray(),
				'itemUrl'		=> $this->view->url()->item($Item)->toString()
			);
			$Log->log(array('log-item-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			$Connection->rollback();
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Done
		return $Item;
	}
	
	protected function _deleteItem ( $type ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Log = Bal_App::getLog();
		$result = true;
		
		# Handle
		try {
			# Start
			$Connection->beginTransaction();
			
			# Fetch
			$Item = $this->_getItem($type);
		
			# Handle
			if ( $Item && $Item->exists() ) {
				# Extract
				$ItemArray = $Item->toArray(true);
		
				# Delete
				$Item->delete();
			
				# Commit
				$Connection->commit();
		
				# Log
				$log_details = array(
					'Item'			=> $ItemArray
				);
				$Log->log(array('log-'.$type.'-delete',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
			}
			else {
				throw new Zend_Exception('error-'.$type.'-missing');
			}
		}
		catch ( Exception $Exception ) {
			# Rollback
			$Connection->rollback();
			
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
			
			# Error
			$result = false;
		}
		
		# Return result
		return $result;
	}
	
	
	# ========================
	# EVENT
	

	public function eventAction ( ) {
		# Redirect
		return $this->_forward('event-list');
	}

	public function eventDeleteAction ( ) {
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action' => 'content-delete', 'type' => 'event'), 'back');
	}

	public function eventEditAction ( ) {
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action' => 'content-edit', 'type' => 'event'), 'back');
	}

	public function eventNewAction ( ) {
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action' => 'content-new', 'type' => 'event'), 'back');
	}

	public function eventListAction ( ) {
		# Redirect
		return $this->getHelper('redirector')->gotoRoute(array('action' => 'content-list', 'type' => 'event'), 'back');
	}

}
