<template>
    <div class="flex border-b border-40">
        <div class="w-1/4 py-4">
            <slot>
                <h4 class="font-normal text-80">
                    {{ field.name }}
                </h4>
            </slot>
        </div>
        <div class="w-3/4 py-4">
            <div v-bind:id="field.mapId" style="width: 100%;" :style="'height: ' + field.height">
            </div>
        </div>
    </div>
</template>

<script>

    import * as GoogleMapLoader from 'google-maps'
    import * as Wkt from 'wicket'
    import * as WktGmap from 'wicket/wicket-gmap3' // not remove

    export default {
        props: ['resource', 'resourceName', 'resourceId', 'field'],

        data: () => ({
            google: undefined,
            gmap: undefined,
            features: [],
            base64img: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAAZCAYAAADXPsWXAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQwIDc5LjE2MDQ1MSwgMjAxNy8wNS8wNi0wMTowODoyMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjM2RDdCMTZGMEVCQjExRUE4MDczOTczQzYyQzVBMTg5IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjM2RDdCMTcwMEVCQjExRUE4MDczOTczQzYyQzVBMTg5Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MzZEN0IxNkQwRUJCMTFFQTgwNzM5NzNDNjJDNUExODkiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MzZEN0IxNkUwRUJCMTFFQTgwNzM5NzNDNjJDNUExODkiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5VXMcaAAABZklEQVR42pyVzysFURTHr3l+RT3PUsKejSRlx8LGxl/wspBkb8nG0lJWYvHYIUmUYuOx9mxkRVGShddIkjw/Pqfu1DSdOz/eqc9tuufM9557+94Zz+gxAdvwBL/gwwlMQ6NJiB4owTv8KXzDBYy5BAbhzvGyJjYTFeiC+5QCATWYDItsZBQIkIULOYYRWIFmZYuvcG3b71TyMvcoD+uOVQ6gzxbnYdFRd2YchynqBWXlPaX2wWPoVYovrTeicajM5UXkS0m0OWzQrszVRORWSYxDf2SuCYpKbVVETrUWYR+mrIuHYQdGldobGYbgLcYLVfiJyc9KJxUox9wn8YLnyMnhH3lWbdPUF7vwHD60SkbLf8KACbUptl7O2MVWcKjhaIDjlF28QLdLXbzxkUJkPqnNpQSBc8glibTYT6Am4CtONnHb8hWRuaweKEYESnV6yaxagSvocBUl/UMWoBXW7P1S41+AAQBLg7MOrTBxVgAAAABJRU5ErkJggg=="
        }),

        mounted() {
            GoogleMapLoader.KEY = this.field.apiKey;
            GoogleMapLoader.LIBRARIES = ['drawing']; // 'geometry', 'places', 'encoding'
            var that = this;
            GoogleMapLoader.load(function (google) {
                that.google = google;
                that.gmap = new google.maps.Map(document.getElementById(that.field.mapId), {
                    center: new google.maps.LatLng(that.field.lat, that.field.lng),
                    defaults: {
                        icon: that.base64img,
                        editable: true,
                        strokeColor: that.field.drawStrokeColor,
                        fillColor: that.field.drawFillColor,
                        fillOpacity: that.field.drawFillOpacity
                    },
                    disableDefaultUI: true,
                    mapTypeControl: false,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControlOptions: {
                        position: google.maps.ControlPosition.TOP_LEFT,
                        style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                    },
                    panControl: false,
                    streetViewControl: false,
                    zoom: that.field.zoom,
                    zoomControl: true,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.LEFT_TOP,
                        style: google.maps.ZoomControlStyle.SMALL
                    }
                });
                google.maps.event.addListener(that.gmap, 'tilesloaded', function () {
                    if (!this.loaded) {
                        this.loaded = true;
                        that.mapIt();
                    }
                });

                that.gmap.drawingManager = new google.maps.drawing.DrawingManager({
                    drawingControlOptions: {
                        position: google.maps.ControlPosition.TOP_CENTER,
                        drawingModes: []
                    },
                    markerOptions: that.gmap.defaults,
                    polygonOptions: that.gmap.defaults,
                    polylineOptions: that.gmap.defaults,
                    rectangleOptions: that.gmap.defaults
                });

                that.gmap.drawingManager.setMap(that.gmap);

            });
        },

        methods: {
            setInitialValue() {
                this.value = this.field.value || ''
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {
                formData.append(this.field.attribute, this.value || '')
            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                this.value = value
            },
            mapIt() {
                this.value = this.field.value || ''
                if (!this.value || this.value === "")
                    return;

                var wkt = new Wkt.Wkt();
                try { // Catch any malformed WKT strings
                    wkt.read(this.value);
                } catch (e1) {
                    try {
                        wkt.read(this.value.replace('\n', '').replace('\r', '').replace('\t', ''));
                    } catch (e2) {
                        if (e2.name === 'WKTError') {
                            console.error("Invalid WKT");
                            return;
                        }
                    }
                }

                var obj = wkt.toObject(this.gmap.defaults);

                if (Wkt.isArray(obj)) { // Distinguish multigeometries (Arrays) from objects
                    for (i in obj) {
                        if (obj.hasOwnProperty(i) && !Wkt.isArray(obj[i])) {
                            obj[i].setMap(this.gmap);
                        }
                    }
                    this.features = this.features.concat(obj);
                } else {
                    obj.setMap(this.gmap); // Add it to the map
                    this.features.push(obj);
                }

                obj.setEditable(false);
                // center to polygon
                var bound = new this.google.maps.LatLngBounds();
                for (const pos of obj.getPath().g) {
                    var position = new this.google.maps.LatLng(pos.lat(), pos.lng())
                    bound.extend(position)
                }
                this.gmap.fitBounds(bound);

                return obj;
            },
        },
    }

</script>
