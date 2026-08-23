---
id: "docs-api-libraries-perl"
title: "Cerb Web-API Library for Perl"
url: "https://cerb.ai/docs/api/libraries/perl/"
summary: "This page provides a Perl library for interacting with the Cerb Web-API, authored by Net Ground. The library, defined in the Cerb_WebAPI.pm module, facilitates HTTP requests to the Cerb API using methods such as GET, PUT, POST, and DELETE. It includes functionality for setting up HTTP headers, managing payloads, and generating authentication signatures using MD5 hashing. The library leverages the WWW::Curl::Easy module for handling HTTP requests and responses, and it includes methods for sorting query strings and managing content types. The code is structured to handle different HTTP verbs and construct the necessary headers for API authentication and communication."
tags: ["docs"]
---
# Cerb\_WebAPI.pm

```
#!/usr/bin/perl
# @author Net Ground / www.netground.nl

use strict;
use warnings;
use POSIX qw(strftime);
use Switch;
use Data::Types ':string';
use URI;
use URI::Escape;
use Digest::MD5 qw(md5_hex);
use WWW::Curl::Easy;

package Cerb_WebAPI;

sub new {
  my $class = shift;
  my $self = {
    _access_key => shift,
    _secret_key => shift,
    _content_type => '',
  };
  bless $self, $class;
  return $self;
}

sub get {
  my $self = shift;
  my $url = shift;
  return $self->_connect('GET', $url);
}

sub put {
  my $self = shift;
  my $url = shift;
  my $payload = shift;
  return $self->_connect('PUT', $url, $payload);
}

sub post {
  my $self = shift;
  my $url = shift;
  my $payload = shift;
  return $self->_connect('POST', $url, $payload);
}

sub delete {
  my $self = shift;
  my $url = shift;
  return $self->_connect('DELETE', $url);
}

sub get_content_type {
  my $self = shift;
  return $self->{_content_type};
}

sub _sort_query_string {
  my $self = shift;
  my $query = shift;
  $query = substr($query,1) if (substr($query,0,1) eq '?');
  my @args = split /&/, $query;
  return join('&', sort @args);
}

sub _connect {
  my $self = shift;
  my $verb = shift;
  my $url = shift;
  my $payload = shift;

  my @header;
  my $ch = WWW::Curl::Easy->new;

  $verb = uc $verb;
  my $http_date = POSIX::strftime("%a, %d %b %Y %H:%M:%S %z", localtime(time()));

  push @header, "Date: $http_date";
  push @header, 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';

  my $postfields = '';

  if (defined $payload) {
    if (ref($payload) eq 'ARRAY') {
      foreach (@{ $payload }) {
        if (ref($_) eq 'ARRAY' && scalar(@{ $_ } == 2)) {
          $postfields .= $_->[0] . '=' . URI::Escape::uri_escape($_->[1]) . '&';
        }
      }
      $postfields =~ s/(.*)&$/$1/gi;
    } elsif (Data::Types::is_string($payload)) {
      $postfields = $payload;
    }
  }

  switch($verb) {
    case 'DELETE' { $ch->setopt(WWW::Curl::Easy::CURLOPT_CUSTOMREQUEST, 'DELETE'); }
    case 'PUT' { push @header, "Content-Length: " . strlen($postfields);
                 $ch->setopt(WWW::Curl::Easy::CURLOPT_CUSTOMREQUEST, 'PUT');
                 $ch->setopt(WWW::Curl::Easy::CURLOPT_POSTFIELDS, $postfields); }
    case 'POST' { push @header, "Content-Length: " . length($postfields);
                  $ch->setopt(WWW::Curl::Easy::CURLOPT_POST, 1);
                  $ch->setopt(WWW::Curl::Easy::CURLOPT_POSTFIELDS, $postfields); }
  }

  my $url_parts = URI->new($url);
  my $url_path = $url_parts->path;
  my $url_query = '';
  if ($url_parts->query) {
    $url_query = $self->_sort_query_string($url_parts->query);
  }

  my $secret = lc(Digest::MD5::md5_hex($self->{_secret_key}));
  my $string_to_sign = "$verb\n$http_date\n$url_path\n$url_query\n$postfields\n$secret\n";
  my $hash = Digest::MD5::md5_hex($string_to_sign);
  push @header, "Cerb-Auth: " . sprintf("%s:%s", $self->{_access_key}, $hash);

  $ch->setopt(WWW::Curl::Easy::CURLOPT_URL, $url);
  $ch->setopt(WWW::Curl::Easy::CURLOPT_HTTPHEADER, \@header) if (@header);
  $ch->setopt(WWW::Curl::Easy::CURLOPT_HEADER, 0);

  my $response_body = '';
  $ch->setopt(WWW::Curl::Easy::CURLOPT_WRITEDATA, \$response_body);

  my $retcode = $ch->perform;
  if ($retcode == 0) {
    return $response_body;
  }

  return undef;
}
1;
```
