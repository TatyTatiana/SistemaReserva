<?php

namespace App\Livewire\Admin\Report;

use App\Models\Campus;
use App\Models\Place;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsByPlaces extends Component
{
    public $placesCount, $campuses, $dateFrom, $dateTo;
    public $campusFilter, $activeFilter = false;

    use WithPagination;

    public function download($id)
    {
        $pathLogo = public_path('images/logo_VRIP.png');
        $pathEscudo = public_path('images/escudo-color-gradiente.png');

        $data = Place::where('id', $id)
            ->with([
                'details',
                'type',
                'seat',
                'building',
                'reservations' => function ($query) {
                    $query->where('active', true)->with('user');
                },
                'reservations.dates',
                'reservations.hours',
                'reservations.services',
            ])
            ->get();

        $dateReservation = $data->flatMap(function ($place) {
            return $place->reservations->flatMap(function ($reservation) {
                return $reservation->dates->pluck('date');
            });
        });

        if (empty($this->dateFrom)) {
            $dateFrom = $dateReservation->min();
        } else {
            $dateFrom = $this->dateFrom;
        }
        if (empty($this->dateTo)) {
            $dateTo = Carbon::today();
        } else {
            $dateTo = $this->dateTo;
        }

        $pending = 0;
        $approved = 0;
        $rejected = 0;
        $totalReservations = 0;

        foreach ($data as $place) {
            foreach ($place->reservations as $reservation) {
                foreach ($reservation->dates as $date) {
                    if ($date->date >= $dateFrom && $date->date <= $dateTo) {
                        $totalReservations++;
                        switch ($reservation->status->value) {
                            case 'PENDIENTE':
                                $pending++;
                                break;
                            case 'APROBADO':
                                $approved++;
                                break;
                            case 'RECHAZADO':
                                $rejected++;
                                break;
                        }
                    }
                }
            }
        }

        $pdf = new Dompdf();
        $pdf->loadHtml(view('pdf.report-place', [
            'data' => $data,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'pathLogo' => $pathLogo,
            'pathEscudo' => $pathEscudo,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalReservations' => $totalReservations
        ])
            ->render());
            
        $this->dispatch('success', 'Reporte generado correctamente.');

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $pdf->setOptions($options);

        $code = optional($data->first())->code;
        $todayDate = Carbon::now()->format('Ymd');

        $pdf->render();
        $pdfOutput = $pdf->output();
        return response()->streamDownload(
            function () use ($pdfOutput) {
                echo $pdfOutput;
            },
            $todayDate . '_' . $code . '.pdf'
        );
    }

    public function filterByCampus()
    {
        $this->campusFilter = ($this->campusFilter == auth()->user()->campus_id) ? auth()->user()->campus_id : $this->campusFilter;
        $this->resetPage();
    }

    public function filterByActive()
    {
        $this->activeFilter = !$this->activeFilter;
        $this->resetPage();
    }

    public function mount()
    {
        $this->campusFilter = auth()->user()->campus_id;
    }

    public function render()
    {
        sleep(1);
        $this->campuses = Campus::where('active', true)->get();

        $places = Place::with(['building.campus', 'details', 'reservations.dates'])
            ->when($this->campusFilter, function ($query, $campusId) {
                $query->whereHas('building.campus', function ($subquery) use ($campusId) {
                    $subquery->where('campus_id', $campusId);
                });
            });

        if (!$this->activeFilter) {
            $places->where('active', true);
        }

        $this->placesCount = $places->count();

        // ORDEN
        $places = $places->orderBy('active', 'desc')
            ->orderBy('code', 'asc')
            ->paginate(10);

        return view('livewire..admin.report.reports-by-places', [
            'places' => $places,
            'campuses' => $this->campuses
        ]);
    }
}
