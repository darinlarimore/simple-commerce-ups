import '../css/cp.css'
import PackageDimensionsFieldtype from './components/PackageDimensionsFieldtype.vue'

Statamic.booting(() => {
	Statamic.$components.register('package-dimensions-fieldtype', PackageDimensionsFieldtype)
})
