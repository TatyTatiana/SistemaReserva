<div>

    <div class="container">

        <div class="opciones_boton mb-3 row row-cols-1 row-cols-md-4 row-cols-lg-4">
            <div class="col text-center pt-3">
                <label for="selectedDates">
                    <h6>Fecha a buscar:</h6>
                </label>
            </div>

            <div class="col mt-2">
                <input wire:model="selectedDates" wire:change="actualizarUnreservedPlaces" class="form-control"
                    type="date" id="selectedDates" required min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
            </div>

            @guest
                <div class="col mt-2">
                    <select wire:model="cityFilter" wire:change="actualizarUnreservedPlaces" class="form-select">
                        <option value="null" selected>Filtrar por ciudad.</option>
                        <option value="CHILLAN">CHILLAN</option>
                        <option value="CONCEPCION">CONCEPCION</option>
                    </select>
                </div>
            @endguest

            @auth
                <div class="col opciones_boton mt-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#placeModal">
                        Agregar Espacio
                    </button>
                </div>
            @endauth

        </div>

        @if (!$unreservedPlaces)
            <div class="mx-auto">
                <h5>No se encuentran espacios disponibles</h5>
            </div>
        @else
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @foreach ($unreservedPlaces as $place)
                    @if ($place->availableHours > 0)
                        <div class="col">
                            <div class="card @if (!$place->active) border-danger text-bg-danger @endif">
                                <div class="card-body">
                                    <h5 class="card-title text-center">{{ $place->code }} <span
                                            class="badge text-bg-info">{{ $place->capacity }}</span></h5>
                                    <div class="card-text">
                                        <div class="m-1 text-center">
                                            <p style="margin-top:0; margin-bottom:0;">Edificio
                                                {{ $place->building->building }}, Piso
                                                {{ $place->floor }}</p>
                                            <p style="margin-top:0; margin-bottom:0;">
                                                {{ $place->building->campus->campus }},
                                                {{ $place->building->campus->city }}</p>
                                        </div>
                                        <div class="m-1 text-center">
                                            @foreach ($place->details as $detail)
                                                <span class="m-1 badge text-bg-info">{{ $detail->detail }}</span>
                                            @endforeach
                                        </div>
                                        <div class="text-center">
                                            <p style="margin-top:0; margin-bottom:0;">Horarios disponibles:</p>
                                            @foreach ($place->availableHours as $hour)
                                                <button class="btn btn-sm btn-secondary mt-1"
                                                    disabled>{{ $hour['formatted_hour'] }}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mt-2 opciones_boton">
                                        @if ($place->active)
                                            <button wire:click="book({{ $place->id }})" class="btn btn-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reservationModal">Reservar</button>
                                        @endif

                                        @auth
                                            <button wire:click="edit({{ $place->id }})" class="btn btn-warning"
                                                data-bs-toggle="modal" data-bs-target="#placeModal"><i
                                                    class="bi bi-pencil-square text-dark"></i></button>
                                            @if ($place->active)
                                                <button wire:click="delete({{ $place->id }})" class="btn btn-danger"><i
                                                        class="bi bi-trash3"></i></button>
                                            @else
                                                <button wire:click="setActive({{ $place->id }})"
                                                    class="btn btn-success"><i class="bi bi-check-lg"></i></button>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        <div wire:ignore.self class="modal fade" id="placeModal" tabindex="-1" aria-labelledby="placeModal"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="placeModalTitle">
                            @if (!$editPlace)
                                Nuevo Espacio
                            @endif
                        </h1>
                        <div class="opciones_boton">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body">

                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session() }}
                            </div>
                        @endif

                        <form wire:submit="store">
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <div class="col">
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.code">Código de sala</label>
                                        <input wire:model="placeEdit.code" id="placeEdit.code" class="form-control"
                                            type="text" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.capacity">Capacidad</label>
                                        <input wire:model="placeEdit.capacity" id="placeEdit.capacity"
                                            class="form-control" type="number" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.floor">Piso</label>
                                        <input wire:model="placeEdit.floor" id="placeEdit.floor" class="form-control"
                                            type="number" name="floor" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.type_id">Tipo de espacio</label>
                                        <select wire:model="placeEdit.type_id" id="placeEdit.type_id"
                                            class="form-select">
                                            <option value="" disabled>Seleccione el tipo de espacio.</option>
                                            @foreach ($types as $type)
                                                <option value="{{ $type->id }}" required>{{ $type->type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.seat_id">Tipo de asiento</label>
                                        <select wire:model="placeEdit.seat_id" id="placeEdit.seat_id"
                                            class="form-select">
                                            <option value="" disabled>Seleccione el tipo de asientos.</option>
                                            @foreach ($seats as $seat)
                                                <option value="{{ $seat->id }}" required>{{ $seat->seat }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mt-2">
                                        <label class="form-label" for="placeEdit.building_id">Ubicación</label>
                                        <select wire:model="placeEdit.building_id" id="placeEdit.building_id"
                                            class="form-select">
                                            <option value="" disabled>Seleccione una ubicación.</option>
                                            @foreach ($buildings as $building)
                                                <option value="{{ $building->id }}" required>
                                                    {{ $building->building }},
                                                    {{ $building->campus->campus }}, {{ $building->campus->city }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label">Detalles del espacio:</label>
                                        <ul style="list-style-type: none;">
                                            @foreach ($details as $detail)
                                                <li>
                                                    <label class="form-check-label"
                                                        for="selectedDetails{{ $detail->id }}">
                                                        <input wire:model="selectedDetails"
                                                            id="selectedDetails{{ $detail->id }}"
                                                            class="form-check-input" type="checkbox"
                                                            value="{{ $detail->id }}">
                                                        {{ $detail->detail }}
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="opciones_boton mt-3">
                                @if (!$editPlace)
                                    <button class="btn btn-primary" type="submit">Agregar</button>
                                @else
                                    <button wire:click="update" class="btn btn-primary">Actualizar</button>
                                @endif
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="reservationModal" tabindex="-1"
            aria-labelledby="reservationModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="reservationModalTitle">
                            {{ $selectedDates }}, Espacio {{ $reservationPlace->code ?? '' }}
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session() }}
                            </div>
                        @endif

                        <form wire:submit="bookSave">
                            <div class="row row-cols-1 row-cols-md-2 g-4 m-2">
                                <div class="col">
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationPlace.data">Espacio:</label>
                                        <input class="form-control" id="reservationPlace.data" type="text"
                                            value="{{ $reservationPlace->code ?? '' }}, {{ $reservationPlace->building->building ?? '' }} - {{ $reservationPlace->building->campus->campus ?? '' }}, {{ $reservationPlace->building->campus->city ?? '' }}"
                                            disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationEdit.name">Nombre</label>
                                        <input wire:model="reservationEdit.name" id="reservationEdit.name"
                                            class="form-control" type="text" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationEdit.email">Correo</label>
                                        <input wire:model="reservationEdit.email" id="reservationEdit.email"
                                            class="form-control @error('reservationEdit.email') is-invalid @enderror"
                                            type="text" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationEdit.userType">Cargo</label>
                                        <input wire:model="reservationEdit.userType" id="reservationEdit.userType"
                                            class="form-control" type="text" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationEdit.activity">Actividad</label>
                                        <input wire:model="reservationEdit.activity" id="reservationEdit.activity"
                                            class="form-control" type="text" required>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label" for="reservationEdit.assistants">Cantidad
                                            asistentes</label>
                                        <input wire:model="reservationEdit.assistants" id="reservationEdit.assistants"
                                            class="form-control" type="number" required>
                                        @error('assistants')
                                            <span class="error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-check-label"
                                            for="reservationEdit.associated_project">Proyecto asociado (Si
                                            hay)</label>
                                        <input wire:model="reservationEdit.associated_project"
                                            id="reservationEdit.associated_project" class="form-check-input"
                                            type="checkbox">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="col">
                                        <div class="mt-2">
                                            <label class="form-label"
                                                for="reservationEdit.comment">Observaciones:</label>
                                            <textarea wire:model="reservationEdit.comment" id="reservationEdit.comment" class="form-control" rows="5"></textarea>
                                        </div>
                                        <div class="mt-2 cols-sm-2 d-grid">
                                            @if (empty($services))
                                                <h6>No hay servicios disponibles</h6>
                                            @else
                                                <p>Servicios disponibles:</p>
                                                <ul style="list-style-type: none;">
                                                    @foreach ($services as $service)
                                                        <li>
                                                            <label class="form-check-label"
                                                                for="selectedServices{{ $service->id }}">
                                                                <input wire:model="selectedServices"
                                                                    id="selectedServices{{ $service->id }}"
                                                                    class="form-check-input" type="checkbox"
                                                                    value="{{ $service->id }}">
                                                                {{ $service->service }}
                                                            </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col">
                                        <p>Horas disponibles:</p>
                                        <div class="form-check">
                                            @foreach ($availableHours as $hour)
                                                <input wire:model="selectedHours" class="btn-check" type="checkbox"
                                                    id="selectedHours{{ $hour['hour']['id'] }}"
                                                    value="{{ $hour['hour']['id'] }}">
                                                <label class="btn btn-outline-secondary btn-sm m-1"
                                                    for="selectedHours{{ $hour['hour']['id'] }}">{{ $hour['formatted_hour'] }}</label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="opciones_boton mt-3">
                                <button class="btn btn-primary" type="submit">Reservar</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
