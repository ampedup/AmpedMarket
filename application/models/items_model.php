<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Items_model extends CI_Model {
	public function __construct(){	
		parent::__construct();
		$this->load->model('images_model');
	}

	// Get information about an item.
	public function getInfo($itemHash){
		$query = $this->db->get_where('items',array('itemHash' => $itemHash));
		if($query->num_rows() > 0){
			return $query->row_array();
		} else {
			return NULL;
		}
	}

	// Add the product to itemPhotos and images.
	public function addProductImage($array){
		// Get the number of photos for that item.
		$query = $this->db->get_where('itemPhotos',array('itemHash' => $array['item']['itemHash']));
		$count = $query->num_rows();

		// Add image to itemPhotos
		$imagePhoto = array(	'itemHash' => $array['item']['itemHash'],
					'imageHash' => $array['imageHash']);
		if(!$this->db->insert('itemPhotos',$imagePhoto)){
			return FALSE;
		}

		// Check if this image is the new main image, or if there are no other images.
		if($array['mainPhoto'] == '1' || $count == 0){
			// Update the items table to store the mainPhoto
			$this->db->where('itemHash', $array['item']['itemHash']);
			$this->db->update('items',array('mainPhotoHash' => $array['imageHash']));		
		}

		// Encode the image and store it in the DB.
		$image = array( 'encoded' => $array['encoded'],
				'imageHash' => $array['imageHash'],
				'height' => '120',
				'width' => '160' );
		if(!$this->db->insert('images',$image)){
			return FALSE;
		}

		return TRUE;
	}

	// Update information about the item.
	public function updateItem($itemHash,$array){
		$this->db->where('itemHash',$itemHash);
		$query = $this->db->update('items',$array);
		if($query){
			return true;
		} else {
			return false;
		}
	}

	// Load  items for the specified user.
	public function userListings($userHash){
		$query = $this->db->get_where('items',array('sellerID' => $userHash));
		$result = $query->result_array();

		//Get more information for each item
		$this->load->model('images_model');
		$this->load->model('currency_model');
		foreach($result AS &$item){
			//Load the main image for this item
			$item['itemImgs'] = $this->get_item_images($item['itemHash'],1);
			//Load the vendors information
			$item['vendor'] = $this->users_model->get_user(array('userHash' => $item['sellerID']));
			$item['symbol'] = $this->currency_model->get_symbol($item['currency']);
		}
		return $result;
	}


	public function getLatest($count = 20){
		// Display most recent items.
		$this->db->order_by('id DESC');
		$query = $this->db->get('items');
		$result = $query->result_array();

		//Get more information for each item
		$this->load->model('images_model');
		$this->load->model('currency_model');
		foreach($result AS &$item){
			//Load the main image for this item
			$item['itemImgs'] = $this->get_item_images($item['itemHash'],1);
			//Load the vendors information
			$item['vendor'] = $this->users_model->get_user(array('userHash' => $item['sellerID']));
			$item['symbol'] = $this->currency_model->get_symbol($item['currency']);
		}
		return $result;
	}


	//Load the requested items from the database
	public function get_items($itemHash = FALSE){
	
		//If no item is specified, load all the items
		if ($itemHash === FALSE){
			return $this->getLatest();
		}

		//Otherwise, load the data for the specified item
		$query = $this->db->get_where('items', array('itemHash' => $itemHash));
		$result = $query->row_array();

		//Check that this item was found
		if ($query->num_rows() > 0){

			//Load all the images for this item
			$this->load->model('images_model');
			$this->load->model('currency_model');
			$result['itemImgs'] = $this->get_item_images($itemHash);
			$result['symbol'] = $this->currency_model->get_symbol($result['currency']);

			//Load the vendors information
			$this->load->model('users_model');
			$result['vendor'] = $this->users_model->get_user(array('userHash' => $result['sellerID']));
			
			return $result;
		} else { //No matching items found
			return NULL;
		}
	}



	//Get the images for a particular item
	public function get_item_images($itemHash = FALSE, $mainPhoto = FALSE){
		$this->load->library('my_image');
	        //If no item ID is given or no images are found for that item. Show the default image.
	        if ($itemHash === FALSE) {
	                $query = $this->db->get_where('itemPhotos', array('id' => 0));
                	$result = $query->row_array();
                	$data = $this->my_image->displayImage($result);
                	return $data;
	        }

		// Load the item from the model.
               	$query = $this->db->get_where('items', array('itemHash' => $itemHash));
       		$result = $query->row_array();

	        //Check if only the main image is requested
	        if($mainPhoto == 1){
			// Check the product exists
			if($query->num_rows() > 0){
				// Load the main image or show the default one.
	                	$itemPhotos = $this->db->get_where('itemPhotos', array('imageHash' => $result['mainPhotoHash']));
				if($itemPhotos->num_rows() > 0){
					// Load image information.
		                	$array = $itemPhotos->row_array();
		                	$variable = $this->my_image->displayImage($array['imageHash']);
				} else {
					// Load the default image.
					$defaultImage = $this->db->get_where('images', array('imageHash' => 'default'));
					$array = $defaultImage->row_array();
					$variable = $this->my_image->displayImage($array['imageHash']);
				}
				// Return image info.
				return $variable;
			}
            	}  else { 
			//Load an array of all images for current item
		    	$variable = array();
                	$getPhotos = $this->db->get_where('itemPhotos', array('itemHash' => $itemHash));
                	$i = 0;
                	foreach($getPhotos->result_array() as $entry){
				$tmp = $this->my_image->displayImage($entry['imageHash']);
				//$tmp['imageHash'] = $array['imageHash'];
                		$variable[$i++] = $tmp;
                	}
                	return $variable;
            	}
	}


	



}