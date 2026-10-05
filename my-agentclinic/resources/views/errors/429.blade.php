@php($seconds = \App\Support\ErrorPage::retryAfter($exception ?? null))
<x-error-page
    code="429"
    :title="__('Too many requests')"
    :message="__('You are asking for a lot all at once. Take a breath, then try again.')"
    :hint="$seconds ? trans_choice('Please wait :count second before trying again.|Please wait :count seconds before trying again.', $seconds) : null"
    :reload="__('Try again')"
/>
