chyrp-meta
==========

Chyrp-Module for inserting facebook open-graph and twittercard meta-tags

Simply install and configure module and insert "${ trigger.call("meta", post, page) }" within <head>-section of template. This is required for getting required data from current post and page.