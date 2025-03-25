<template>
	<div>
		<div class="u-grid u-grid-cols-4 u-gap-2">
			<div class="u-col-span-3">
				<div class="u-text-xs u-mb-1">Package Type</div>
				<select-input
					:options="packageOptions"
					:value="value.package_type"
					@input="updatePackageType"
					:placeholder="'Select Package Type'"
				/>
			</div>
			<div>
				<div class="u-text-xs u-mb-1">Package Separately?</div>
				<toggle-input
					:value="value.package_separately"
					@input="updatePackageSeparately"
					:label="'Package Separately'"
				/>
			</div>
		</div>

		<div class="u-grid u-grid-cols-4 u-gap-2 u-mt-4">
			<div>
				<div class="u-text-xs u-mb-1">{{ weightLabel }}</div>
				<text-input :value="value.weight" @input="updateWeight" :placeholder="weightLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ heightLabel }}</div>
				<text-input :value="value.height" @input="updateHeight" :placeholder="heightLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ widthLabel }}</div>
				<text-input :value="value.width" @input="updateWidth" :placeholder="widthLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ lengthLabel }}</div>
				<text-input :value="value.length" @input="updateLength" :placeholder="lengthLabel" />
			</div>
		</div>
	</div>
</template>


<script>
export default {
	mixins: [Fieldtype],
	data() {
		return {
			packageOptions: [
				{ value: '00', label: 'UNKNOWN' },
				{ value: '01', label: 'UPS Letter' },
				{ value: '02', label: 'Package' },
				{ value: '03', label: 'Tube' },
				{ value: '04', label: 'Pak' },
				{ value: '21', label: 'Express Box' },
				{ value: '24', label: '25KG Box' },
				{ value: '25', label: '10KG Box' },
				{ value: '30', label: 'Pallet' },
				{ value: '2a', label: 'Small Express Box' },
				{ value: '2b', label: 'Medium Express Box' },
				{ value: '2c', label: 'Large Express Box' }
			]
		};
	},
	computed: {
		lengthLabel() {
			return this.config.metric ? 'Length (cm)' : 'Length (in)';
		},
		widthLabel() {
			return this.config.metric ? 'Width (cm)' : 'Width (in)';
		},
		heightLabel() {
			return this.config.metric ? 'Height (cm)' : 'Height (in)';
		},
		weightLabel() {
			return this.config.metric ? 'Weight (kg)' : 'Weight (lbs)';
		}
	},
	methods: {
		updatePackageType(value) {
			this.update({ ...this.value, package_type: value });
		},
		updatePackageSeparately(value) {
			this.update({ ...this.value, package_separately: value });
		},
		updateLength(value) {
			this.update({ ...this.value, length: value });
		},
		updateWidth(value) {
			this.update({ ...this.value, width: value });
		},
		updateHeight(value) {
			this.update({ ...this.value, height: value });
		},
		updateWeight(value) {
			this.update({ ...this.value, weight: value });
		}
	}
};
</script>
