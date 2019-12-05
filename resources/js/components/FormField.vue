<template>

    <default-field :field="field" :errors="errors">
        <template slot="field">
            <div class="map-container">
                <div v-bind:id="field.mapId" style="width: 100%;" :style="'height: ' + field.height">
                </div>
                <div class="clear-button" v-on:click="clearMap" v-if="!field.readonly">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="#666666">
                        <path d="M8 6V4c0-1.1.9-2 2-2h4a2 2 0 0 1 2 2v2h5a1 1 0 0 1 0 2h-1v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8H3a1 1 0 1 1 0-2h5zM6 8v12h12V8H6zm8-2V4h-4v2h4zm-4 4a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0v-6a1 1 0 0 1 1-1zm4 0a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0v-6a1 1 0 0 1 1-1z"/>
                    </svg>
                </div>
            </div>
        </template>
    </default-field>
</template>

<script>
    import {FormField, HandlesValidationErrors} from 'laravel-nova'
    import * as GoogleMapLoader from 'google-maps'
    import * as Wkt from 'wicket'
    import * as WktGmap from 'wicket/wicket-gmap3' // not remove

    export default {
        mixins: [FormField, HandlesValidationErrors],

        props: ['resourceName', 'resourceId', 'field'],

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
                        drawingModes: that.field.readonly ? [] : that.field.drawingModes
                    },
                    markerOptions: that.gmap.defaults,
                    polygonOptions: that.gmap.defaults,
                    polylineOptions: that.gmap.defaults,
                    rectangleOptions: that.gmap.defaults
                });

                that.gmap.drawingManager.setMap(that.gmap);

                google.maps.event.addListener(that.gmap.drawingManager, 'overlaycomplete', function (event) {
                    var wkt;
                    that.clearMap();
                    // Set the drawing mode to "pan" (the hand) so users can immediately edit
                    this.setDrawingMode(null);
                    // Polygon drawn
                    if (event.type === google.maps.drawing.OverlayType.POLYGON || event.type === google.maps.drawing.OverlayType.POLYLINE) {
                        // New vertex is inserted
                        google.maps.event.addListener(event.overlay.getPath(), 'insert_at', function (n) {
                            that.updateWktString();
                        });
                        // Existing vertex is removed (insertion is undone)
                        google.maps.event.addListener(event.overlay.getPath(), 'remove_at', function (n) {
                            that.updateWktString();
                        });
                        // Existing vertex is moved (set elsewhere)
                        google.maps.event.addListener(event.overlay.getPath(), 'set_at', function (n) {
                            that.updateWktString();
                        });
                    } else if (event.type === google.maps.drawing.OverlayType.RECTANGLE) { // Rectangle drawn
                        // Listen for the 'bounds_changed' event and update the geometry
                        google.maps.event.addListener(event.overlay, 'bounds_changed', function () {
                            that.updateWktString();
                        });
                    }

                    that.features.push(event.overlay);
                    wkt = new Wkt.Wkt();
                    wkt.fromObject(event.overlay);
                    that.handleChange(wkt.write());
                });

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
                if (!Wkt.isArray(obj) && wkt.type !== 'point') {
                    var that = this;
                    this.google.maps.event.addListener(obj.getPath(), 'insert_at', function (n) {
                        that.updateWktString();
                    });
                    // Existing vertex is removed (insertion is undone)
                    this.google.maps.event.addListener(obj.getPath(), 'remove_at', function (n) {
                        that.updateWktString();
                    });
                    // Existing vertex is moved (set elsewhere)
                    this.google.maps.event.addListener(obj.getPath(), 'set_at', function (n) {
                        that.updateWktString();
                    });
                } else {
                    if (obj.setEditable) {
                        obj.setEditable(false);
                    }
                }

                if (Wkt.isArray(obj)) { // Distinguish multigeometries (Arrays) from objects
                    for (i in obj) {
                        if (obj.hasOwnProperty(i) && !Wkt.isArray(obj[i])) {
                            obj[i].setMap(this.gmap);
                        }
                        if (wkt.type !== 'point') {
                            // New vertex is inserted
                            this.google.maps.event.addListener(obj[i].getPath(), 'insert_at', function (n) {
                                this.updateWktStringPart();
                            });
                            // Existing vertex is removed (insertion is undone)
                            this.google.maps.event.addListener(obj[i].getPath(), 'remove_at', function (n) {
                                this.updateWktStringPart();
                            });
                            // Existing vertex is moved (set elsewhere)
                            this.google.maps.event.addListener(obj[i].getPath(), 'set_at', function (n) {
                                this.updateWktStringPart();
                            });
                        }
                    }
                    this.features = this.features.concat(obj);
                } else {
                    obj.setMap(this.gmap); // Add it to the map
                    this.features.push(obj);
                }
                // Pan the map to the feature
                if (obj.getBounds !== undefined && typeof obj.getBounds === 'function') {
                    // For objects that have defined bounds or a way to get them
                    this.gmap.fitBounds(obj.getBounds());
                } else {
                    if (obj.getPath !== undefined && typeof obj.getPath === 'function') {
                        // For Polygons and Polylines
                        this.gmap.panTo(obj.getPath().getAt(0));
                    } else { // But points (Markers) are different
                        if (obj.getPosition !== undefined && typeof obj.getPosition === 'function') {
                            this.gmap.panTo(obj.getPosition());
                        }
                    }
                }

                // center to polygon
                var bound = new this.google.maps.LatLngBounds();
                for (const pos of obj.getPath().g) {
                    var position = new this.google.maps.LatLng(pos.lat(), pos.lng())
                    bound.extend(position)
                }
                this.gmap.fitBounds(bound);

                if(this.field.readonly){
                    obj.setEditable(false);
                }

                return obj;
            },
            clearMap() {
                var i;
                // Reset the remembered last string (so that we can clear the map,
                //  paste the same string, and see it again)
                this.value = '';
                for (i in this.features) {
                    if (this.features.hasOwnProperty(i)) {
                        this.features[i].setMap(null);
                    }
                }
                this.features.length = 0;
                this.handleChange(this.value);
            },
            updateWktString() {
                var wkt = new Wkt.Wkt();
                wkt.fromObject(this.features[0]);
                this.handleChange(wkt.write());
            },
            updateWktStringPart() {
                var i, w, v;
                w = new Wkt.Wkt(this.features[0]);
                i = 1;
                while (i < this.features.length) {
                    v = new Wkt.Wkt(this.features[i]);
                    w.merge(v);
                    i += 1;
                }
                this.handleChange(wkt.write());
            },
        },
    }
</script>
<style>
    .map-container {
        position: relative;
    }

    .clear-button {
        cursor: pointer;
        width: 24px;
        height: 24px;
        padding: 4px;
        position: absolute;
        top: 5px;
        right: 5px;
        background: white;
        border-radius: 2px;
        box-shadow: rgba(0, 0, 0, 0.3) 0 1px 4px -1px;
    }

    .clear-button:hover {
        background: #eee;
    }

</style>
