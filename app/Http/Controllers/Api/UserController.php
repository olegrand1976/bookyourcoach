<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Liste des utilisateurs",
     *      description="Retourne la liste de tous les utilisateurs",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Liste des utilisateurs récupérée avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="users",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/User")
     *              )
     *          )
     *      )
     * )
     */
    public function index()
    {
        $users = \App\Models\User::all();
        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
