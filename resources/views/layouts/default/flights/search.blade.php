<h3 class="description">@lang('flights.search')</h3>
<div class="card border-blue-bottom">
	<div class="card-body ml-1 mr-1" style="min-height: 0px; display: flex; justify-content: center; align-items: center;">
		<div class="form-group search-form">
			{{ Form::open([
				'route' => 'frontend.flights.search',
				'method' => 'GET',
				'class'=>'form-inline'
			]) }}
			<div class="mt-1">
				<p>@lang('common.airline')</p>
				@php asort($airlines); @endphp
				{{ Form::select('airline_id', $airlines, null , ['class' => 'form-control select2']) }}
			</div>
      
			<div class="mt-1">
				<p>@lang('flights.flightnumber')</p>
				{{ Form::text('flight_number', null, ['class' => 'form-control']) }}
			</div>

			<div class="mt-1">
				<p>@lang('airports.departure')</p>
				{{ Form::select('dep_icao', $airports, null , ['class' => 'form-control select2']) }}
			</div>

			<div class="mt-1">
				<p>@lang('airports.arrival')</p>
				{{ Form::select('arr_icao', $airports, null , ['class' => 'form-control select2']) }}
			</div>

			<div class="mt-1">
				<p>@lang('common.subfleet')</p>
				@php asort($subfleets); @endphp
				{{ Form::select('subfleet_id', $subfleets, null , ['class' => 'form-control select2']) }}
			</div>

			<div class="clear mt-1" style="margin-top: 10px;">
				{{ Form::submit(__('common.find'), ['class' => 'btn btn-outline-primary']) }}&nbsp;
				<a href="{{ route('frontend.flights.index') }}">@lang('common.reset')</a>
			</div>
			{{ Form::close() }}
		</div>
	</div>
</div>
