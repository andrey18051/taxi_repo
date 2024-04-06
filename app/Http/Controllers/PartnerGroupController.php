<?php

namespace App\Http\Controllers;

use App\Models\PartnerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(PartnerGroup::get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $partnerGroup = new PartnerGroup();
        $partnerGroup->name = " ";
        $partnerGroup->description = " ";
        $partnerGroup->save();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(
        $id,
        $name,
        $description
    ) {
        $partner = PartnerGroup::find($id);

        $partner->name = $name;
        $partner->description = $description;
        $partner->save();

        return response()->json(PartnerGroup::find($id));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $message = PartnerGroup::find($id);

            if (!$message) {
                return response()->json(['error' => 'Группа не найдена'], 404);
            }

            $message->delete();

            DB::commit();

            return response()->json(['message' => 'Группа успешно удалена'], 204);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Произошла ошибка при удалении сообщения', 'details' => $e->getMessage()], 500);

        }
    }
}
