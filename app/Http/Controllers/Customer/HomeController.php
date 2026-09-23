<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $warehouses = [
            [
                'name' => 'Gudang A5',
                'block' => 'Blok A5',
                'area' => '4.324 m²',
                'price' => 'Rp15.000.000',
                'facilities' => ['Loading Dock', 'CCTV', '+2'],
                'image' => 'images/warehouses/warehouse-a5.png',
            ],
            [
                'name' => 'Gudang A6',
                'block' => 'Blok A6',
                'area' => '5.500 m²',
                'price' => 'Rp16.000.000',
                'facilities' => ['Loading Dock', 'Security', '+2'],
                'image' => 'images/warehouses/warehouse-a5.png',
            ],
            [
                'name' => 'Gudang B2',
                'block' => 'Blok B2',
                'area' => '3.800 m²',
                'price' => 'Rp13.500.000',
                'facilities' => ['CCTV', 'Office', '+2'],
                'image' => 'images/warehouses/warehouse-a5.png',
            ],
            [
                'name' => 'Gudang C1',
                'block' => 'Blok C1',
                'area' => '6.200 m²',
                'price' => 'Rp18.000.000',
                'facilities' => ['Loading Dock', 'Office', '+2'],
                'image' => 'images/warehouses/warehouse-a5.png',
            ],
        ];

        return view('customer.home', ['warehouses' => $warehouses]);
    }
}
