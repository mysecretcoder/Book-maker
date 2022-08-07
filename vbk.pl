#!/usr/local/bin/perl -w
#
# bookmarker verification perl script
# see vbk.sh for a handy shell script to run this
# via nohup
#
# make sure and update the database connection
# variables for your installation below.
#
use strict;

# use DBI.pm generic database interface
use DBI;

# use LWP for HTTP automation
use LWP::UserAgent;

# CGI.pm module
# set to -no_debug since we run from command line
# use standard HTML plus HTML 3 additions
# use Pretty formatted HTML so it is readable
# send errors to HTML output
use CGI qw(-no_debug :standard :html3);
use CGI::Pretty;
use CGI::Carp 'fatalsToBrowser';

my $ua = new LWP::UserAgent;
$ua->agent("bookmarker/1.0");
$ua->timeout(20);

my $driver = 'mysql';
my $database = 'db';
my $options = '';
my $user = 'user';
my $password = 'password';

my $title = 'Bookmarker URL Verification Report';

print start_html(-title=>$title,
                   -dtd=>1,
		   -BGCOLOR=>'white');

print h2 ($title);

my $dsn = "DBI:$driver:database=$database;$options";
 
my $dbh = DBI->connect($dsn, $user, $password) || die "Connect Failed: $DBI::errstr \n ";
 
my $sth = $dbh->prepare('select id, url, name from bookmark order by id')  || die "Select Failed: $DBI::errstr \n ";

$sth->execute || die "Execute Failed: $DBI::errstr \n ";

my ($id, $url, $name, $request, $response, $msg, $errs);

my @headings = ('ID','Bookmark', 'Response');
my @rows = th(\@headings);
     
$errs = 0;
while ( ($id, $url, $name) = $sth->fetchrow_array ) {

  $request = new HTTP::Request('HEAD',$url);
  $response = $ua->request($request); # fetch!
      
  if ($response->is_error) {
    $errs += 1;
    $msg = $response->code . ": " . $response->message;
    push(@rows, td([ a({-href=>"maintain.php3?id=$id"},$id)
                    ,a({-href=>$url},$name)
		    ,$msg]
		 ));
  }

}        	     
$sth->finish();
$dbh->disconnect;

if ( $errs > 0 ) {
  print strong ("$errs Failed URL Requests:");
  print table({-border=>'1',-width=>'90%'},
             Tr(\@rows)
             );
} else {
  print strong ('No Failed Requests!');
}

print end_html;

exit 0;
