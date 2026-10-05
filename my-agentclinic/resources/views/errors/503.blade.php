@php($seconds = \App\Support\ErrorPage::retryAfter($exception ?? null))
<x-error-page
    code="503"
    :title="__('Back soon')"
    :message="__('The clinic is closed for maintenance. The couch is being reupholstered.')"
    :hint="$seconds ? trans_choice('Please try again in about :count minute.|Please try again in about :count minutes.', max(1, (int) ceil($seconds / 60))) : null"
    :reload="__('Try again')"
    :home="false"
    :language="false"
/>
