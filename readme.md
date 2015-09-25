# Elastic

Elastic is an ElasticSearch integration for MODX, to index resources (and perhaps other types of content) into an  ElasticSearch instance.

## Installation

Upload the files into your MODX site, and open /_bootstrap/ in the browser. This will set up namespaces, settings and elements for you. To use the Scheduler task for indexing all data, install Scheduler before running the bootstrap. 

After that, head over to your system settings and the elastic namespace. These settings are important:

- elastic.hosts: set this to a comma separated list of elasticsearch server hosts/IPs including the port. The ElasticSearch client will pick one. Just setting one host works fine too.
- elastic.resource_index: the index name for your resource content. This can be something like "resource", but also your site name. 
- elastic.resource_type: the type name you want your resource content to live under, like "content" or "resources". 

In the future this might become available as installable package.  