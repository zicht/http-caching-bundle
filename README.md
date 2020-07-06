# `zicht/http-caching-bundle`

This bundle serves as a general optimization for caching content statically in
a reverse proxy. It simply covers some basic rules for serving content with
correct HTTP caching headers.

## Installation

Just like any regular bundle, install it with composer, and add it to your
AppKernel.

## Configuration

Add to your bundle configuration:

```
zicht_http_caching:
    urls:
        -
            pattern: # a regex 
            private: # a number identifying number of seconds for "private" responses
            public: # a number of seconds for "public" responses
```

This configuration means that for any url matching the regex, a cache header is
added to the response indicating that the response may be cached for the
specified amount of seconds.

The difference between "private" and "public" responses, is that any response
that is sent without a Set-Cookie, in response to a request that had no
Authorization or Cookie headers, is considered "public".

# Maintainers
