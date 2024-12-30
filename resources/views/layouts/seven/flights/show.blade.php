@extends('app')
@section('title', trans_choice('common.flight', 1).' '.$flight->ident)

@section('content')
  <div class="row">
    <div class="col-lg-8">
      <div class="row">
        <div class="col-12">
              <div class="row">
                <div class="col-sm-12">
                  <div class="flex-row justify-content-between d-flex">
                    <div class="d-flex flex-row center-align" style="font-size: 1.4rem; line-height: 1.4rem; font-weight: 600; text-align: center">
                      @if(optional($flight->airline)->logo)
                          <img src="{{ $flight->airline->logo }}" alt="{{$flight->airline->name}}"
                            style="max-width: 80px; width: 100%; height: auto;"/>
                      @else
                        {{ $flight->airline->name }}: 
                      @endif
                      <span class="ms-1">
                      @if($flight->airline->iata)
                      {{ $flight->airline->icao }}{{$flight->flight_number}} |
                      @endif
                      {{ $flight->ident }}
                      @if(filled($flight->callsign) && !setting('simbrief.callsign', true))
                        {{ '| '. $flight->atc }}
                      @endif
                      </span>
                      </div>
                      <div><span class="badge bg-secondary">{{$flight->flight_type}}&nbsp;<span class="d-none d-sm-inline">({{\App\Models\Enums\FlightType::label($flight->flight_type)}})</span></span></div>
                  </div>
                  <div class="my-2 d-flex flex-row justify-content-between">
                    <div class="d-flex flex-column text-start">
                      <div class="fs-2" style="font-weight: 600">
                        <a href="{{ route('frontend.airports.show', [$flight->dpt_airport_id]) }}">
                          {{ $flight->dpt_airport_id }}
                        </a>
                      </div>
                      <div class="fs-5 d-none d-md-flex">{{$flight->dpt_airport->name}}</div>
                      <div class="fs-5">
                        {{$flight->dpt_time}}
                      </div>
                    </div>
                    <div class="d-flex flex-column text-end">
                      <div class="fs-2" style="font-weight: 600">
                        <a href="{{ route('frontend.airports.show', [$flight->arr_airport_id]) }}">
                          {{$flight->arr_airport_id}}
                        </a>
                      </div>
                      <div class="fs-5 d-none d-md-flex">{{$flight->arr_airport->name}}</div>
                      <div class="fs-5">
                        {{$flight->arr_time}}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex flex-row justify-content-between">
                    <div class="text-center fs-5">
                      @if($flight->flight_time)@minutestotime($flight->flight_time)@endif{{$flight->flight_time && $flight->distance ? '/' : ''}}{{$flight->distance ? $flight->distance.'nm' : ''}}
                    </div>
                    <div class="fs-5">
                      @if(count($flight->subfleets) !== 0)
                        @php
                          $arr = [];
                          foreach ($flight->subfleets as $sf) {
                              $tps = explode('-', $sf->type);
                              $type = last($tps);
                              $arr[] = "{$sf->type}";
                          }
                        @endphp
                        {{implode(", ", $arr)}}
                      @else
                        Any Subfleet
                      @endif
                    </div>
                  </div>
                </div>
              </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          @include('flights.map')
        </div>
      </div>
      @if(filled($flight->notes))
        <div class="row mt-3">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                Notes
              </div>
              <div class="card-body">
                {!! $flight->notes !!}
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
    <div class="col-lg-4">
      <h5>{{$flight->dpt_airport_id}} @lang('common.metar')</h5>
      {{ Widget::Weather([
          'icao' => $flight->dpt_airport_id,
        ]) }}
      <br/>
      <h5>{{$flight->arr_airport_id}} @lang('common.metar')</h5>
      {{ Widget::Weather([
          'icao' => $flight->arr_airport_id,
        ]) }}
      @if ($flight->alt_airport_id)
        <br/>
        <h5>{{$flight->alt_airport_id}} @lang('common.metar')</h5>
        {{ Widget::Weather([
            'icao' => $flight->alt_airport_id,
          ]) }}
      @endif
    </div>
  </div>
@endsection
