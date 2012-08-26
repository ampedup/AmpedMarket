<?php

class Orders_model extends CI_Model {
		
	public function __construct(){
		$this->load->library('my_session');
		$this->load->model('currency_model');
		$this->load->model('items_model');
	}

	public function nextStep($orderID,$fromStep){
		$orderInfo = $this->getOrderByID($orderID);
		if($orderInfo === NULL){
			return NULL;
		} else {
			$this->db->select('step');
			$this->db->where('id',$orderID);
			$query = $this->db->get('orders');
			$result = $query->result();
			if($result[0]->step !== $fromStep){
				return NULL;
			}
			$this->db->where('id',$orderID);
			$query = $this->db->update('orders',array('step' => ($result[0]->step+1)));
			if($query){
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}

	public function myOrders($sellerHash = NULL){
		$this->load->model('users_model');
		$buyer = $this->my_session->userdata('userHash');

		$this->db->order_by('time DESC');
		if($sellerHash !== NULL && $this->users_model->get_user(array('userHash' => $sellerHash)) !== FALSE){
			$getOrders = $this->db->get_where('orders',array('buyerHash' => $buyer,
									 'sellerHash' => $sellerHash)
							);
		} else {
			$getOrders = $this->db->get_where('orders',array('buyerHash' => $buyer));
		}

		$orders = $this->buildOrderArray($getOrders);
		
		if($orders === FALSE){
			return NULL;
		} else {
			return $orders;
		}
	}	

	public function buildOrderArray($order){
		if($order->num_rows() > 0){
			$i = 0;
				//print_r($result);
			foreach($order->result() as $result){
				
				$tmp = $result->items;
				$items = explode(":", $tmp);
				$j = 0;
				
				foreach($items as $item){
					$array = explode("-", $item);
					$itemInfo[$j] = $this->items_model->getInfo($array[0]);
					$itemInfo[$j++]['quantity'] = $array[1];
				}


				if($result->step == '0'){
					$stepMessage = anchor('order/place/'.$result->sellerHash,'Place Order');
				} else if($result->step == '1'){
					$stepMessage = 'Vendor awaiting payment.';
				} else if($result->step == '2'){
					$stepMessage = 'Awaiting dispatch.';
				} else if($result->step == '3'){
					$stepMessage = "Completed. ".anchor('order/review/'.$result->sellerHash,'Please Review');
				} else {//Error..
					$stepMessage = 'Gone on too long!';
				}

				$orders[$i++] = array(	'id' => $result->id,
							'seller' => $this->users_model->get_user(array('userHash'=> $result->sellerHash)),
							'buyer' => $this->users_model->get_user(array('userHash'=> $result->buyerHash)),
							'totalPrice' => $result->totalPrice,
							'currency' => $result->currency,
							'currencySymbol' => $this->currency_model->get_symbol($result->currency),
							'time' => $result->time,
							'dispTime' => $this->general->displayTime($result->time),
							'items' => $itemInfo,
							'step' => $result->step,
							'progress' => $stepMessage );
				unset($itemInfo);
			}
//			print_r($orders);
			return $orders;
		} else {
			return FALSE;
		}
	}

	public function ordersByStep($userID,$step){
		$query = $this->db->get_where('orders', array('sellerHash' => $userID,
							  'step' => $step ) );
		$orders = $this->buildOrderArray($query);

		if($orders === FALSE){
			return NULL;
		} else {
			return $orders;
		}
	}

	public function check($buyer,$seller,$step=NULL){
		//Check there is no ongoing orders between this buyer and vendor
		if($step == NULL){
			$key = 'step !=';
			$val = 3;
		} else {
			$key = 'step';
			$val = $step;
		}
		$query = $this->db->get_where('orders',array(	'buyerHash' => $buyer,
								'sellerHash' => $seller,
								$key => $val));
		if($query->num_rows() > 0){
			return $this->buildOrderArray($query);
		} else {
			return NULL;
		}
	}

	public function createOrder($orderInfo){
		$query = $this->db->insert('orders',$orderInfo);
		if($query){ 
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function getOrderByID($id){
		$query = $this->db->get_where('orders', array('id' => $id));
		if($query->num_rows() > 0){
			return $this->buildOrderArray($query);
		} else {
			return NULL;
		}
	}

	public function updateOrder($newInfo){
		// Get current order
		$currentOrder = $this->getOrderByID($newInfo['id']);
		
		// Build a list of new items.
		$found = false;			// Is the item currently in the order?
		$newItems = '';			
		$place = 0;			// Placeholder for formatting.

		if(count($currentOrder)>0){
			// Loop through each item in the order
			foreach($currentOrder[0]['items'] as $item){
				
				// Check if the item is found on the list.
				if($item['itemHash'] == $newInfo['itemHash']){
					$quantity = ($newInfo['quantity']);
					$found = true;
				} else {
					$quantity = $item['quantity'];
				}
	
				// Check the submitted quantity is greater than 0. 
				if($quantity > 0){
					if($place++ !== 0)
						$newItems .= ":";
					// Add the itemHash and quantity.
					$newItems.= $item['itemHash']."-".$quantity;
				}
				
			}
			// Finish off new items, add the item if it's not already held in the order.
			if($found === false){
				if($newInfo['quantity'] > 0)
					$newItems.= ":".$newInfo['itemHash']."-".$newInfo['quantity'];
			}
	
		
	
			if(!empty($newItems)){
				// Regenerate the total price.
				$splitNewItems = explode(":",$newItems);
				$totalPrice = 0;
				foreach($splitNewItems as $item){
					$info = explode("-",$item);
					$quantity = $info[1];
					$itemInfo = $this->items_model->getInfo($info[0]);
					$totalPrice += $quantity*$itemInfo['price'];
				
				}	

				$order = array( 'items' => $newItems,
					'totalPrice' => $totalPrice,
					'time' => time() );
	
				$this->db->where('id',$currentOrder[0]['id']);
				if($this->db->update('orders',$order)){
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				$this->db->where('id',$currentOrder[0]['id']);
				if($this->db->delete('orders')){
					return 'DROP';
				}
			}
		}
		return NULL;
	}


	public function getQuantity($itemHash){
		
		// Determine buyerHash and sellerHash
		$buyerHash = $this->my_session->userdata('userHash');
		$itemInfo = $this->items_model->getInfo($itemHash);
		$sellerHash = $itemInfo['sellerID'];

		// Load order information
		$getOrder = $this->check($buyerHash,$sellerHash);
	
		foreach($getOrder[0]['items'] as $item){
			if($item['itemHash'] == $itemHash){
				return $item['quantity'];
			}
		}
	
		return NULL;
	}
};

