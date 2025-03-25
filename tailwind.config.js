module.exports = {
	content: ['./resources/**/*'],
	corePlugins: {
		preflight: false,
	},
	prefix: 'u-',
	darkMode: ['variant', ['html[class*="dark"] &']],
}
