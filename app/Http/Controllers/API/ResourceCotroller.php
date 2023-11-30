<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResourceCotroller extends Controller
{
    public function index()
    {
        return Resource::all();
    }

    public function store(Request $request)
    {
        // $data = $request->validate([
        //     'name' => 'required|string',
        // ]);
        $resource = new Resource;
        $resource->id = Str::uuid(); // Generate a UUID
        $resource->name = 'Your Resource Name';
        $resource->save();

        return response()->json($resource, 201);
    }

    public function show($id)
    {
        $resource = Resource::where('id', $id)->get();
        
        return response()->json($resource, 201);;
    }

    public function update(Request $request, Resource $resource)
    {
        $data = $request->validate([
            'name' => 'string',
        ]);

        $resource->update($data);

        return response()->json($resource, 200);
    }

    public function destroy(Resource $resource)
    {
        $resource->delete();

        return response()->json(null, 204);
    }
}
