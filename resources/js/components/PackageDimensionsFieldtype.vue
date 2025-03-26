<template>
	<div>

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
		<div class="u-flex u-gap-2 u-mt-4">
			<div>
				<div class="u-text-xs u-mb-1">Package Separately?</div>
				<toggle-input
					:value="value.package_separately"
					@input="updatePackageSeparately"
					:label="'Package Separately'"
				/>
			</div>
		</div>
	</div>
</template>


<script>
export default {
	mixins: [Fieldtype],
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
