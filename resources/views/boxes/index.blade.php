@extends('statamic::layout')

@section('title', 'UPS Boxes')

@section('content')
	<div id="boxes-container">
		<header class="u-mb-3">
			<div class="u-flex u-items-center u-justify-between">
				<h1>{{ __('UPS Box Sizes') }}</h1>
				<button class="btn-primary" id="showModalBtn">Add Box</button>
			</div>
		</header>

		<div class="card u-p-0">
			<table class="data-table">
				<thead>
					<tr>
						<th class="u-pl-2">Name</th>
						<th>Length</th>
						<th>Width</th>
						<th>Height</th>
						<th>Box Weight</th>
						<th class="u-pr-2">Max Weight</th>
						<th class="u-pr-2"></th>
					</tr>
				</thead>
				<tbody>
					@forelse($boxes as $box)
					<tr>
						<td class="u-pl-2">{{ $box['name'] }}</td>
						<td>{{ $box['boxLength'] }}{{ $isMetric ? 'mm' : 'in' }}</td>
						<td>{{ $box['boxWidth'] }}{{ $isMetric ? 'mm' : 'in' }}</td>
						<td>{{ $box['boxHeight'] }}{{ $isMetric ? 'mm' : 'in' }}</td>
						<td>{{ $box['boxWeight'] }}{{ $isMetric ? 'g' : 'lbs' }}</td>
						<td class="u-pr-2">{{ $box['maxWeight'] }}{{ $isMetric ? 'g' : 'lbs' }}</td>
						<td class="u-pr-2">
							<form method="POST" action="{{ route('statamic.cp.boxes.destroy', $box['id']) }}" onsubmit="return confirm('Are you sure you want to delete this box?');" class="inline">
								@csrf
								@method('DELETE')
								<button type="submit" class="btn-close">×</button>
							</form>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="5" class="u-text-center u-p-3 u-text-gray-500">No boxes defined</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="modal" id="modal" style="display: none;">
			<div class="modal-card">
				<h2 class="u-p-2 u-text-lg">Add New Box</h2>
				<form action="{{ route('statamic.cp.boxes.store') }}" method="POST">
					@csrf
					<div class="u-p-3">
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Name</label>
							<input type="text" name="name" class="input-text" required>
						</div>
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Length ({{ $isMetric ? 'mm' : 'in' }})</label>
							<input type="number" name="boxLength" class="input-text" required>
						</div>
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Width ({{ $isMetric ? 'mm' : 'in' }})</label>
							<input type="number" name="boxWidth" class="input-text" required>
						</div>
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Height ({{ $isMetric ? 'mm' : 'in' }})</label>
							<input type="number" name="boxHeight" class="input-text" required>
						</div>
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Box Weight ({{ $isMetric ? 'g' : 'lbs' }})</label>
							<input type="number" name="boxWeight" class="input-text" required>
						</div>
						<div class="u-mb-3">
							<label class="u-font-bold u-text-gray-800">Max Weight ({{ $isMetric ? 'g' : 'lbs' }})</label>
							<input type="number" name="maxWeight" class="input-text" required>
						</div>
					</div>
					<div class="u-p-2 u-flex u-justify-end u-gap-2">
						<button type="button" class="btn" id="cancelBtn">Cancel</button>
						<button type="submit" class="btn-primary">Add Box</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const modal = document.getElementById('modal');
			const showModalBtn = document.getElementById('showModalBtn');
			const cancelBtn = document.getElementById('cancelBtn');

			showModalBtn.addEventListener('click', function() {
				modal.style.display = 'block';
			});

			cancelBtn.addEventListener('click', function() {
				modal.style.display = 'none';
			});
		});
	</script>
@endsection
