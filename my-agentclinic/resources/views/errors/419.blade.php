<x-error-page
    code="419"
    :title="__('Page expired')"
    :message="__('Your session sat on the couch too long and nodded off. Reload the page and try again.')"
    :reload="true"
    :login="true"
/>
