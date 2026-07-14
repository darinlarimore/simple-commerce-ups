<template>
	<div>

		<div class="u-grid u-grid-cols-4 u-gap-2 u-mt-4">
			<div>
				<div class="u-text-xs u-mb-1">{{ weightLabel }}</div>
				<ui-input :model-value="value.weight" @update:model-value="updateWeight" :placeholder="weightLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ heightLabel }}</div>
				<ui-input :model-value="value.height" @update:model-value="updateHeight" :placeholder="heightLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ widthLabel }}</div>
				<ui-input :model-value="value.width" @update:model-value="updateWidth" :placeholder="widthLabel" />
			</div>
			<div>
				<div class="u-text-xs u-mb-1">{{ lengthLabel }}</div>
				<ui-input :model-value="value.length" @update:model-value="updateLength" :placeholder="lengthLabel" />
			</div>
		</div>
		<div class="u-flex u-gap-2 u-mt-4">
			<div>
				<div class="u-text-xs u-mb-1">Package Separately?</div>
				<ui-switch
					:model-value="Boolean(value.package_separately)"
					@update:model-value="updatePackageSeparately"
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
