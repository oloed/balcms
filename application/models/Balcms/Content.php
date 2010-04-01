<?php

/**
 * Balcms_Content
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
class Balcms_Content extends Base_Balcms_Content
{

	/**
	 * Apply modifiers
	 * @return
	 */
	public function setUp ( ) {
		$this->hasMutator('Avatar', 'setAvatar');
		$this->hasMutator('position', 'setPosition');
		$this->hasMutator('path', 'setPath');
		$this->hasMutator('code', 'setCode');
		parent::setUp();
	}
	
	/**
	 * Set the User's Avatar
	 * @return string
	 */
	protected function setMediaAttachment ( $what, $value ) {
		# Prepare
		$Media = Media::fetch($value);
		
		# Apply Media
		if ( $Media === null || $Media ) {
			if ( isset($this->$what) ) {
				$this->$what->delete();
			}
			$this->_set($what, $Media ? $Media : null, false);
		}
		
		# Done
		return true;
	}
	
	/**
	 * Set the User's Avatar
	 * @return string
	 */
	public function setAvatar ( $value ) {
		return $this->setMediaAttachment('Avatar', $value);
	}
	
	/**
	 * Get's the content's crumbs
	 * @param const $hydrateMode [optional]
	 * @param bool $includeSelf [optional]
	 * @return mixed
	 */
	public function getCrumbs ( $includeSelf = true, $hydrateMode = null ) {
		# Prepare
		$Crumbs = array();
		$Crumb = $this;
		while ( $Crumb->parent_id ) {
			$Crumb = $Crumb->Parent;
			$Crumbs[] = $hydrateMode === Doctrine::HYDRATE_ARRAY ? $Crumb->toArray() : $Crumb;
		}
		
		# Include?
		if ( $includeSelf ) {
			$Crumbs[] = $hydrateMode === Doctrine::HYDRATE_ARRAY ? $this->toArray() : $this;
		}
		
		# Done
		return $Crumbs;
	}
	
	/**
	 * Convert the content to a navigation item
	 * @param mixed $Content
	 * @return array
	 */
	public static function toNavItem ( $Content ) {
		# Prepare
		$Content = $Content;
		$Content_Route = delve($Content,'Route');
		if ( is_object($Content_Route) ) $Content_Route = $Content_Route->toArray();
		
		# Convert
		$content = array(
			'id' => 'content-'.delve($Content,'code'),
			'route' => 'map',
			'label' => delve($Content,'title'),
			'title' => delve($Content,'tagline',delve($Content,'title')),
			'order' => delve($Content,'position'),
			'params' => array(
				'Map' => $Content_Route
			),
			'route' => 'map'
		);
		
		# Return content
		return $content;
	}
	
	/**
	 * Fetched the crumbs as navigation items
	 * @param bool $includeSelf [optional] defaults to true
	 * @return array
	 */
	public function getCrumbsNavigation ( $includeSelf = true ) {
		# Fetch
		$Crumbs = $this->getCrumbs($includeSelf);
		
		# To Navigation
		foreach ( $Crumbs as &$Crumb ) {
			$Crumb = Content::toNavItem($Crumb);
		}
		
		# Return Crumbs
		return $Crumbs;
	}
	
	/**
	 * Sets the code field
	 * @param int $code
	 * @return bool
	 */
	public function setCode ( $code, $load = true ) {
		$code = strtolower($code);
		$code = preg_replace('/[\s_]/', '-', $code);
		$code = preg_replace('/[^-a-z0-9]/', '', $code);
		$code = preg_replace('/--+/', '-', $code);
		$this->_set('code', $code, $load);
		return true;
	}
	
	/**
	 * Sets the position
	 * @param int $position [optional] defaults to id
	 * @return bool
	 */
	public function setPosition ( $position, $load = true ) {
		# Has Changed?
		if ( $this->position != $position && $position > 0 ) {
			$this->_set('position', $position, $load);
			return true;
		}
		
		# No Change
		return false;
	}

	/**
	 * Sets the Route's path field
	 * @param int $path [optional]
	 * @return bool
	 */
	public function setPath ( $path, $load = true ) {
		# Prepare
		$save = false;
		# Prepare
		$path = trim($path, '/');
		if ( empty($path) ) {
			return false;
		}
		# Update
		if ( $this->route_id ) {
			$Route = $this->Route;
		} else {
			$Route = new Route();
			$Route->type = 'content';
			$Route->data = array('id' => $this->id);
			$this->Route = $Route;
			$save = true;
		}
		# Apply
		if ( $Route->path != $path ) {
			$Route->path = $path;
			$Route->save();
			# Update Children
			$Children = $this->Children;
			foreach ( $Children as $Child ) {
				$Child->setPath($path.'/'.$Child->code);
			}
		}
		# Done
		return $save;
	}
	
	
	/**
	 * Get Subscribers Query
	 * @param constant $hydrateMode
	 * @param Doctrine_Query $SubscriberQuery
	 */
	public function getSubscribersQuery ( $hydrateMode = null ) {
		$tags = $this->ContentTagsNames;
		$SubscribersQuery = Doctrine_Query::create()->select('u.*')->from('User u, u.SubscriptionTags uSubscription')->where('u.status = ?', 'published')->andWhereIn('uSubscription.name', $tags)->orderBy('u.id ASC');
		if ( empty($tags) ) {
			$SubscribersQuery->andWhere('true = false');
		}
		if ( !is_null($hydrateMode) ) {
			$SubscribersQuery->setHydrationMode($hydrateMode);
		}
		return $SubscribersQuery;
	}
	
	/**
	 * Get Unsent Subscribers Query
	 * @param constant $hydrateMode
	 * @param Doctrine_Query $SubscriberQuery
	 */
	public function getUnsentSubscribersQuery ( $hydrateMode = null ) {
		$SubscribersQuery = $this->getSubscribersQuery($hydrateMode);
		$SubscribersQuery->andWhere('NOT EXISTS (SELECT m.id FROM Message m WHERE m.For.id = u.id AND m.Content.id = ?)', $this->id);
		return $SubscribersQuery;
	}
	
	/**
	 * Get Subscribers
	 * @param constant $hydrateMode
	 * @param Doctrine_Query $SubscriberQuery
	 */
	public function getSubscribers ( $hydrateMode = null ) {
		$SubscribersArray = array();
		if ( $this->id ) {
			$SubscribersQuery = $this->getSubscribersQuery($hydrateMode);
			$SubscribersArray = $SubscribersQuery->execute();
		}
		return $SubscribersArray;
	}
	
	/**
	 * Get Unsent Subscribers
	 * @param constant $hydrateMode
	 * @param Doctrine_Query $SubscriberQuery
	 */
	public function getUnsentSubscribers ( $hydrateMode = null ) {
		$SubscribersArray = array();
		if ( $this->id ) {
			$SubscribersQuery = $this->getUnsentSubscribersQuery($hydrateMode);
			$SubscribersArray = $SubscribersQuery->execute();
		}
		return $SubscribersArray;
	}
	
	/**
	 * Ensure Properties
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureProperties ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$Content = $Event->getInvoker();
		$modified = $Content->getModified();
		
		# Ensure Position
		if ( !$this->position && $this->id ) {
			$Content->set('position',$this->id,false);
			$save = true;
		}
		
		# Ensure Path
		if ( array_key_exists('code', $modified) && $this->code ) {
			$path = $this->code;
			if ( $this->parent_id )
				$path = trim($this->Parent->Route->path,'/') . '/' . trim($path,'/');
			$Content->set('path',$path,false);
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure the Render of the Content and Description
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureRender ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		$View = Bal_App::getView();
		
		# Fetch
		$Content = $Event->getInvoker();
		$modified = $Content->getModified();
		
		# Content
		if ( array_key_exists('content', $modified) ) {
			# Render Content
			$content_rendered = $View->content()->renderContent($Content);
			$Content->set('content_rendered', $content_rendered, false);
			# Save
			$save = true;
		}
		
		# Description
		if ( array_key_exists('description', $modified) ) {
			# Auto
			$Content->set('description_auto',false,false);
			# Render Description
			$description_rendered = $View->content()->renderDescription($Content);
			$Content->set('description_rendered', $description_rendered, false);
			# Save
			$save = true;
		}
		elseif ( $Content->description_auto || !$Content->description ) {
			# Auto
			$Content->set('description_auto',true,false);
			# Render Description
			$description_rendered = substr(preg_replace('/\s\s+/',' ',strip_tags($Content->content_rendered)), 0, 1000);
			if ( reallyempty($description_rendered) ) $description_rendered = '<!--[empty/]-->';
			$Content->set('description', $description_rendered, false);
			$Content->set('description_rendered', $description_rendered, false);
			# Save
			$save = true;
		}
		
		# Return save
		return $save;
	}
	
	/**
	 * Ensure Tags
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureContentTags ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave','postSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Handle
		return Bal_Doctrine_Core::ensureTags($Event,'ContentTags','tags');
	}
	
	/**
	 * Ensure Send out to Subscribers
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureSend ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('postSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$Content = $Event->getInvoker();
		$modified = $Content->getLastModified();
		
		# Subscription Message
		if ( $Content->status === 'published' && array_key_exists('content_rendered', $modified) ) {
			# Update Message
			$Receivers = $this->getUnsentSubscribers();
			foreach ( $Receivers as $Receiver ) {
				$Message = new Message();
				$Message->For = $Receiver;
				$Message->Content = $Invoker;
				$Message->useTemplate('content-subscription');
				$Message->save();
			}
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Consistency
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensure ( $Event, $Event_type ){
		return Bal_Doctrine_Core::ensure($Event,$Event_type,array(
			'ensureProperties',
			'ensureContentTags',
			'ensureRender',
			'ensureSend'
		));
	}
	
	/**
	 * Backup old values
	 * @param Doctrine_Event $Event
	 */
	public function preSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * Ensure
	 * @param Doctrine_Event $Event
	 * @return string
	 */
	public function postSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
	
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			$Invoker->save();
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}

	/**
	 * Ensure Route id exists
	 * @param Doctrine_Event $Event
	 * @return string
	 */
	public function postInsert ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$Route = $Invoker->Route;
		$result = true;
		
		# Ensure
		if ( !$Route->data['id'] ) {
			$data = $Route->data;
			$data['id'] = $Invoker->id;
			$Route->data = $data;
			$Route->save();
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
}