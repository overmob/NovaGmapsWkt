Nova.booting((Vue, router, store) => {
    Vue.component('index-nova-gmaps-wkt', require('./components/IndexField'))
    Vue.component('detail-nova-gmaps-wkt', require('./components/DetailField'))
    Vue.component('form-nova-gmaps-wkt', require('./components/FormField'))
})
