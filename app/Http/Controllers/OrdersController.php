<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Repositories\OrderRepositoryInterface;

class OrdersController extends Controller
{
	protected $order;

	function __construct(OrderRepositoryInterface $order)
	{
		$this->order = $order;
		
	}
    
    public function index()
    {
    	dd($this->order->getAll());

    	return View::make(orders.index);
    }

}
