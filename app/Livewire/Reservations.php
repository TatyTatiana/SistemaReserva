<?php

namespace App\Livewire;

use App\Enums\ReservationStatusEnum;
use App\Mail\ReservationStatusEmail;
use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class Reservations extends Component
{
    public $reservations, $statusFilter = null, $selectedServices = [], $selectedHours = [], $allServices = [], $showServices = [];
    public $placeEdit, $clientEdit, $hours = [], $reservationId, $comment;

    public $userCity, $userCampus;
    use WithPagination;

    public $reservationEdit = [
        'activity' => '',
        'assistants' => '',
        'associated_project' => '',
        'comment' => '',
        'selectedServices' => []
    ];

    public function show($id)
    {
        $this->reservationId = $id;
        $reservation = Reservation::with('place', 'client', 'hours', 'services')->find($id);
        $this->reservationEdit['activity'] = $reservation->activity;
        $this->reservationEdit['assistants'] = $reservation->assistants;
        $this->reservationEdit['associated_project'] = $reservation->associated_project;
        $this->reservationEdit['comment'] = $reservation->comment;

        $this->placeEdit = $reservation->place;
        $this->clientEdit = $reservation->client;
        $this->hours = $reservation->hours;
        $this->showServices = $reservation->services;
    }

    public function edit($id)
    {
        $reservation = Reservation::with('place', 'client', 'hours', 'services')->find($id);
        $this->reservationId = $id;
        $this->reservationEdit['activity'] = $reservation->activity;
        $this->reservationEdit['assistants'] = $reservation->assistants;
        $this->reservationEdit['associated_project'] = $reservation->associated_project;
        $this->reservationEdit['comment'] = $reservation->comment;

        $this->placeEdit = $reservation->place;
        $this->clientEdit = $reservation->client;
        $this->hours = $reservation->hours;
        $this->reservationEdit['selectedServices'] = $reservation->services->pluck('id')->toArray();

        $allServices = Service::all();
        $this->allServices = $allServices;
    }

    public function update()
    {
        $this->validate([
            'reservationEdit.activity' => 'required',
            'reservationEdit.assistants' => 'required',
        ]);
        $reservation = Reservation::with('services')->find($this->reservationId);
        $reservation->update([
            'comment' => $this->comment,
            'activity' => $this->reservationEdit['activity'],
            'associated_project' => $this->reservationEdit['associated_project'],
            'assistants' => $this->reservationEdit['assistants'],
        ]);
        $reservation->services()->sync($this->reservationEdit['selectedServices']);

        $this->reset();
    }

    public function delete($id)
    {
        $reservation = Reservation::find($id);
        $reservation->update([
            'active' => false
        ]);
    }

    public function statusApproved($id)
    {
        $reservation = Reservation::with(['client'])->where('id', $id)->first();
        $reservation->update([
            'status' => ReservationStatusEnum::approved,
            'user_id' => auth()->user()->id
        ]);
        // Email
        Mail::to($reservation->client->email)->send(new ReservationStatusEmail($reservation->id));
    }

    public function statusReject($id)
    {
        $reservation = Reservation::with(['client'])->where('id', $id)->first();
        $reservation->update([
            'status' => ReservationStatusEnum::rejected,
            'user_id' => auth()->user()->id
        ]);
        // Email
        Mail::to($reservation->client->email)->send(new ReservationStatusEmail($reservation->id));
    }

    public function filterByStatus($status)
    {
        $this->statusFilter = ($this->statusFilter == $status) ? null : $status;
    }

    public function render()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $this->userCity = $user->city;
            $this->userCampus = $user->campus;
            // $this->reservations = Reservation::with('place', 'client', 'hours', 'dates', 'place.buildings')
            //     ->where('city', $this->userCity)
            //     ->where('campus', $this->userCampus)
            //     ->
            //     ->where('active', true)
            //    ->get();

            // $this->reservations = Reservation::where('active', true)
            //     ->with('client', 'hours', 'dates')
            //     ->with('place')
            //     ->whereHas('building' , function ($query) {
            //         $query->where('city', $this->userCity)
            //             ->where('campus', $this->userCampus)
            //             ->get();
            //     });
            $reservations = Reservation::where('active', true)
                ->whereHas('place.building', function ($query) {
                    $query->where('city', $this->userCity)
                        ->where('campus', $this->userCampus);
                })
                ->with('client', 'hours', 'dates', 'place.building')
                ->get();

        } else if ($this->statusFilter == 'ALL') {
            $reservations = Reservation::where('active', true)
            ->with('place', 'client', 'hours', 'dates')
            ->get();
        }
        $this->reservations = $reservations;

        return view('livewire.reservations', [
            'reservations' => $this->reservations,
        ]);
    }
}
