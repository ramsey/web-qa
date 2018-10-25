<?php
include("include/functions.php");

$TITLE = "Submit Build Test [PHP-QAT: Quality Assurance Team]";
$SITE_UPDATE = date("D M d H:i:s Y T", filectime(__FILE__));

common_header();
?>

<h1>Test framework tests</h1>

<p>The easiest way to test your PHP build is to run <code>make test</code> from
the command line after successfully compiling. This will run the all tests for
all enabled functionalities and extensions located in tests folders under the
source root directory using the PHP CLI binary.</p>

<p><code>make test</code> basically executes <code>run-tests.php</code> script
under the source root (parallel builds will not work). Therefore you can execute
the script as follows:</p>

<pre>
TEST_PHP_EXECUTABLE=sapi/cli/php \
sapi/cli/php [-c /path/to/php.ini] run-tests.php [ext/foo/tests/GLOB]
</pre>

<h2>Which php executable does make test use?</h2>

<p>If you are running the <code>run-tests.php</code> script from the command
line (as above) you must set the <code>TEST_PHP_EXECUTABLE</code> environment
variable to explicitly select the PHP executable that is to be tested, that is,
used to run the test scripts.</p>

<p>If you run the tests using make test, the PHP CLI and CGI executables are
automatically set for you. <code>make test</code> executes
<code>run-tests.php</code> script with the CLI binary.  Some test scripts such
as session must be executed by CGI SAPI. Therefore, you must build PHP with CGI
SAPI to perform all tests.</p>

<p>NOTE: PHP binary executing <code>run-tests.php</code> and php binary used for
executing test scripts may differ. If you use different PHP binary for executing
<code>run-tests.php</code> script, you may get errors.

<h2>Which php.ini is used?</h2>

<p><code>make test</code> uses the same php.ini file as it would once installed.
The tests have been written to be independent of that php.ini file, so if you
find a test that is affected by a setting, please report this, so we can address
the issue.</p>

<h2>Which test scripts are executed?</h2>

<p>The <code>run-tests.php</code> (<code>make test</code>), without any arguments
executes all test scripts by extracting all directories named <code>tests</code>
from the source root and any subdirectories below. If there are files, which
have a <code>phpt</code> extension, <code>run-tests.php</code> looks at the
sections in these files, determines whether it should run it, by evaluating the
<code>SKIPIF</code> section. If the test is eligible for execution, the
<code>FILE</code> section is extracted into a <code>.php</code> file (with the
same name besides the extension) and gets executed. When an argument is given or
TESTS environment variable is set, the GLOB is expanded by the shell and any file
with extension <code>*.phpt</code> is regarded as a test file.</p>

<p>Tester can easily execute tests selectively with as follows:</p>

<pre>
./sapi/cli/php run-tests.php ext/mbstring/*
./sapi/cli/php run-tests.php ext/mbstring/020.phpt
</pre>

<h2>Test results</h2>

<p>Test results are printed to standard output. If there is a failed test, the
<code>run-tests.php</code> script saves the result, the expected result and the
code executed to the test script directory. For example, if ext/myext/tests/myext.phpt
fails to pass, the following files are created:</p>

<pre>
ext/myext/tests/myext.php   - actual test file executed
ext/myext/tests/myext.log   - log of test execution (L)
ext/myext/tests/myext.exp   - expected output (E)
ext/myext/tests/myext.out   - output from test script (O)
ext/myext/tests/myext.diff  - diff of .out and .exp (D)
</pre>

<p>Failed tests are always bugs. Either the test is bugged or not considering
factors applying to the tester's environment, or there is a bug in PHP. If this
is a known bug, we strive to provide bug numbers, in either the test name or the
file name. You can check the status of such a bug, by going to:
https://bugs.php.net/12345 where 12345 is the bug number. For clarity and
automated processing, bug numbers are prefixed by a hash sign '#' in test names
and/or test cases are named bug12345.phpt.</p>

<p>NOTE: The files generated by tests can be selected by setting the environment
variable TEST_PHP_LOG_FORMAT. For each file you want to be generated use the
character in brackets as shown above (default is LEOD). The php file will be
generated always.</p>

<p>NOTE: You can set environment variable TEST_PHP_DETAILED to enable detailed
test information.</p>

<h2>Automated testing</h2>

<p>If you like to keep up to speed, with latest developments and quality
assurance, setting the environment variable NO_INTERACTION to 1, will not prompt
the tester for any user input.</p>

<p>Normally, the exit status of <code>make test</code> is zero, regardless of
the results of independent tests. Set the environment variable REPORT_EXIT_STATUS
to 1, and <code>make test</code> will set the exit status ("$?") to non-zero,
when an individual test has failed.</p>

<p>Example script to be run by cron:</p>

<pre>
========== qa-test.sh =============
#!/bin/sh

CO_DIR=$HOME/cvs/php7
MYMAIL=qa-test@domain.com
TMPDIR=/var/tmp
TODAY=`date +"%Y%m%d"`

# Make sure compilation environment is correct
CONFIGURE_OPTS='--disable-all --enable-cli --with-pcre'
export MAKE=gmake
export CC=gcc

# Set test environment
export NO_INTERACTION=1
export REPORT_EXIT_STATUS=1

cd $CO_DIR
cvs update . >>$TMPDIR/phpqatest.$TODAY
./cvsclean ; ./buildconf ; ./configure $CONFIGURE_OPTS ; $MAKE
$MAKE test >>$TMPDIR/phpqatest.$TODAY 2>&1
if test $? -gt 0
then
        cat $TMPDIR/phpqatest.$TODAY | mail -s"PHP-QA Test Failed for $TODAY" $MYMAIL
fi
========== end of qa-test.sh =============
</pre>

<p>NOTE: The exit status of <code>run-tests.php</code> will be 1 when
REPORT_EXIT_STATUS is set. The result of <code>make test</code> may be higher
than that. At present, gmake 3.79.1 returns 2, so it is advised to test for
non-zero, rather then a specific value.</p>

<p>When <code>make test</code> finished running tests, and if there are any failed
tests, the script asks to send the logs to the PHP QA mailinglist. Please answer
<code>y</code> to this question so that we can efficiently process the results,
entering your e-mail address (which will not be transmitted in plaintext to any
list) enables us to ask you some more information if a test failed. Note that
this script also uploads <code>php -i</code> output so your hostname may be
transmitted.</p>

<p>
Specific tests can also be executed, like running tests for a certain extension.
To do this you can do like so (for example the standard library):
<code>make test TESTS=ext/standard</code>. Where <code>TESTS=</code> points to a
directory containing <code>.phpt</code> files or a single <code>.phpt</code> file like:
<code>make test TESTS=tests/basic/001.phpt</code>. You can also pass options directly
to the underlaying script that runs the test suite (<code>run-tests.phpt</code>) using
<code>TESTS=</code>, for example to check for memory leaks using Valgrind, the
<code>-m</code> option can be passed along: <code>make test TESTS="-m Zend/"</code>.
For a full list of options that can be passed along, then run <code>make test TESTS=-h</code>.
</p>

<p>
<strong>Windows users:</strong> On Windows the make command is called <code>nmake</code>
instead of <code>make</code>. This means that on Windows you will have to run
<code>nmake test</code>, to run the test suite.
</p>

<?php
common_footer();
?>
