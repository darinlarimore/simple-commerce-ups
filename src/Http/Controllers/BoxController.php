<?php

namespace Darinlarimore\SimpleCommerceUps\Http\Controllers;

use Illuminate\Http\Request;
use Darinlarimore\SimpleCommerceUps\Services\UPS;

class BoxController
{
    public function index()
    {
        $ups = new UPS();
        return view('simple-commerce-ups::boxes.index', [
            'boxes' => $ups->getBoxes()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'boxLength' => 'required|numeric',
            'boxWidth' => 'required|numeric',
            'boxHeight' => 'required|numeric',
            'boxWeight' => 'required|numeric',
            'maxWeight' => 'required|numeric',
        ]);

        $ups = new UPS();
        $ups->addCustomBox($validated);

        return redirect()->back()->with('success', 'Box added successfully');
    }

    public function destroy($id)
    {
        $ups = new UPS();
        $ups->deleteBox($id);

        return redirect()->back()->with('success', 'Box deleted successfully');
    }
}
