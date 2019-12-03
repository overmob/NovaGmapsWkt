<template>

    <default-field :field="field" :errors="errors">
        <template slot="field">
            <div v-bind:id="field.mapId" style="width: 100%;" :style="'height: ' + field.height">
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
            // wkt: undefined,
            features: [],
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
                        icon: 'red_dot.png',
                        shadow: 'dot_shadow.png',
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
                        // NOTE: We start with a MULTIPOLYGON; these aren't easily deconstructed, so we won't set this object to be editable in this example
                        that.mapIt();
                    }
                });

                that.gmap.drawingManager = new google.maps.drawing.DrawingManager({
                    drawingControlOptions: {
                        position: google.maps.ControlPosition.TOP_CENTER,
                        drawingModes: [
                            // google.maps.drawing.OverlayType.MARKER,
                            // google.maps.drawing.OverlayType.POLYLINE,
                            google.maps.drawing.OverlayType.POLYGON
                            // google.maps.drawing.OverlayType.RECTANGLE
                        ]
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
                    that.field.wkt = wkt.write();
                    that.handleChange(that.field.wkt);
                });

            });
        },

        methods: {
            setInitialValue() {
                this.value = this.field.wkt || ''
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {
                // formData.append(this.field.attribute, this.value || '')
            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                this.value = value
            },
            mapIt() {
                if (!this.field.wkt || this.field.wkt === "")
                    return;

                var wkt = new Wkt.Wkt();
                try { // Catch any malformed WKT strings
                    wkt.read(this.field.wkt);
                } catch (e1) {
                    try {
                        wkt.read(this.field.wkt.replace('\n', '').replace('\r', '').replace('\t', ''));
                    } catch (e2) {
                        if (e2.name === 'WKTError') {
                            console.error("Invalid WKT")
                            return;
                        }
                    }
                }

                var obj = wkt.toObject(this.gmap.defaults);
                if (!Wkt.isArray(obj) && wkt.type !== 'point') {
                    this.google.maps.event.addListener(obj.getPath(), 'insert_at', function (n) {
                        this.updateWktString();
                    });
                    // Existing vertex is removed (insertion is undone)
                    this.google.maps.event.addListener(obj.getPath(), 'remove_at', function (n) {
                        this.updateWktString();
                    });
                    // Existing vertex is moved (set elsewhere)
                    this.google.maps.event.addListener(obj.getPath(), 'set_at', function (n) {
                        this.updateWktString();
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
                return obj;
            },
            clearMap() {
                var i;
                // Reset the remembered last string (so that we can clear the map,
                //  paste the same string, and see it again)
                this.field.wkt = '';
                for (i in this.features) {
                    if (this.features.hasOwnProperty(i)) {
                        this.features[i].setMap(null);
                    }
                }
                this.features.length = 0;
                this.handleChange(this.field.wkt);
            },
            updateWktString() {
                var wkt = new Wkt.Wkt();
                wkt.fromObject(this.features[0]);
                this.field.wkt = wkt.write();
                this.handleChange(this.field.wkt);
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
                this.field.wkt = w.write();
                this.handleChange(this.field.wkt);
            },
        },
    }
</script>
