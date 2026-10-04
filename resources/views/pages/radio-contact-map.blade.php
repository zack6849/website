<x-base-page title="Radio Contacts" vue="true">
    <qso-map
        mapbox-key="{{config('services.mapbox.token')}}"
        :config="@js($mapConfig)"
        :qth="@js($qth)"
    />
</x-base-page>
