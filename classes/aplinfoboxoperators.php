<?php

/*!
  \class OnlineOperators
  \brief The class OnlineOperators
*/

class AplInfoboxOperators
{
	const NOT_STOPPER = 0;
	const STOPPER = 1;
	const STOPPER_SUBTREE = 2;		
    /*!
     Constructor
    */
    function OnlineOperators()
    {
    }

    /*!
     Returns the operators in this class
    */
    function operatorList()
    {
        return array( 'infobox', 'multi_infobox');
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    /*!
     See eZTemplateOperator::namedParameterList()
    */
    function namedParameterList()
    {      
        return array( 'infobox' => array('node_id' => array('type' => 'integer', 
                                                            'required' => true, 
                                                            'default' => ''),
                             		  	 'infobox_class' =>  array( 'type' => 'text', 
                                                                   	'required' => true, 
                                                                    'default' => ''),
                             		  	 'enabled_stopper' =>  array( 'type' => 'text', 
                                                                   	  'required' => true, 
                                                                      'default' => true)),
					 'multi_infobox' => array('node_id' => array('type' => 'integer', 
                                                                 'required' => true, 
																 'default' => ''),
                             		  	      'infobox_class' =>  array( 'type' => 'text', 
                                                                   	     'required' => true, 
                                                                         'default' => '')));
    }

    /*!
     Executes the needed operator(s).
     Checks operator names, and calls the appropriate functions.
    */
    function modify( $tpl, &$operatorName, &$operatorParameters, &$rootNamespace, 
                     &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'infobox':
            {			
            	$node_id = $namedParameters['node_id'];    
            	$infobox_class = $namedParameters['infobox_class'];   
				$enabled_stopper = $namedParameters['enabled_stopper'];   
				$node = eZContentObjectTreeNode::fetch($node_id);
				
				if (is_object($node)) 
				{
					$node_array = array($node);				
					$ancestors_path = $node ->fetchPath();
					
					if (!is_array($ancestors_path))
					{
						$ancestors_path = array();
					}
					
					$branch_path = array_reverse(array_merge($ancestors_path, $node_array));  			
					
					if (is_array($branch_path))
					{         	
						$operatorValue = self::getInfobox( $branch_path,  $infobox_class, $enabled_stopper, $node_id);			
					}
				}
				break;
            }            
			case 'multi_infobox':
            {			
            	$node_id = $namedParameters['node_id'];    
            	$infobox_class = $namedParameters['infobox_class'];   
				$node = eZContentObjectTreeNode::fetch($node_id);
				
				if (is_object($node)) 
				{
					$node_array = array($node);				
					$ancestors_path = $node ->fetchPath();
					
					if (!is_array($ancestors_path))
					{
						$ancestors_path = array();
					}
					
					$branch_path = array_reverse(array_merge($ancestors_path, $node_array));  			
					
					if (is_array($branch_path))
					{         	
						$operatorValue = self::getMultiInfobox( $branch_path,  $infobox_class, $node_id);			
					}
				}
				break;
            } 
        }
    }

    public function getInfobox( $branch_path,  $infobox_class, $enabled_stopper=true, $node_id)
    {       	
    	$infobox_ini = eZINI::instance('infobox.ini');
    	$infobox_stopper_attribute = $infobox_ini -> variable('GeneralSettings', 'InfoboxStoperAttribute');
    	$infobox = false;
    	$infobox_parent = false;
    	foreach ($branch_path as $ancestor) 
		{
			$current_node_id = $ancestor -> NodeID;
			$infoboxes = self::getInfoboxes ($current_node_id, $infobox_class);	
			
			if (isset($infoboxes[0]))
			{
				$infobox = $infoboxes[0];
			}		
			
			if (is_object($infobox))
			{
				$stopper_type = false;
				
				if ($enabled_stopper)
					$stopper_type = self::stopperType($infobox, $infobox_stopper_attribute);
					
				if ( $stopper_type == self::NOT_STOPPER )
				{
					break;			
				}	
				elseif ( $stopper_type == self::STOPPER ) 
				{
					if ($node_id == $current_node_id) 
					{
						$infobox_parent = false;
						$infobox = false;
						break;
					}
				}
				else
				{
					$infobox_parent = false;
					$infobox = false;
					break;
				}			
			}			
		}		
		
		if ($infobox_parent)
		{
			$infobox = $infobox_parent;
		}
		
		return $infobox;        
    }    

    public function getMultiInfobox( $branch_path,  $infobox_class, $node_id)
    {       	
    	$infobox = false;
    	foreach ($branch_path as $ancestor) 
		{
			$current_node_id = $ancestor -> NodeID;
			$infobox = self::getInfoboxes ($current_node_id, $infobox_class);		
			
			if ($infobox)
			{
				return $infobox;
				break;					
			}
			
		}		       
    }        
	
    public function getInfoboxes ($parent_node, $infobox_class) 
	{
		$functionColletion = new eZContentFunctionCollection();
		$fetch_result = $functionColletion -> fetchObjectTree($parent_node, 
												array('priority','true'),
												false,
												false,
												0,
												false,
												1,
												'eq',
												false,
												false,
												false,
												'include',
												array($infobox_class),
												false,
												false,
												false,
												false,
												true,
												false,
												true);	
								
		if (isset($fetch_result['result']))				
		{			
			if (is_array($fetch_result['result']) && count($fetch_result['result']) > 0)
			{
				return $fetch_result['result'];
			}
			else
			{
				return false;	
			}
		}
		return false;				
	} 

    function stopperType($infobox, $infobox_stopper_attribute)
    {
		$data_map = $infobox -> dataMap();
		if (array_key_exists($infobox_stopper_attribute, $data_map))
		{
			$stopper_attibute = $data_map[$infobox_stopper_attribute]; 
			if (is_object($stopper_attibute))
			{
				$stopper_content = 	$stopper_attibute -> content();	
				if($stopper_content['0'] == 1)
				{
					return self::STOPPER;
				}
				elseif($stopper_content['0'] == 2)
				{
					return self::STOPPER_SUBTREE;
				}
			}
		}
		return self::NOT_STOPPER;
    }	
    
}
?>
