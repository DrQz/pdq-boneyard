<?php
/*
 * iis.c
 *
 * Based on Microsoft WAS measurements of IIS.
 * 
 * CMG 2001 paper.
 *
 * PHP5 Translation by Samuel Zallocco - University of L'Aquila - ITALY
 * e-mail: samuel.zallocco@univaq.it
 *
 */

require_once "..\..\..\Lib\PDQ_Lib.php";

error_reporting(0); //  Turning off all error due to division by zero generated by $u1dat, $u2dat and $u3dat values

//-------------------------------------------------------------------------

function  main()
{
	global $job;

	$noNodes = 0;
	$noStreams = 0;
	$users = 0;
	$delta = 0;
	$model = "IIS Server";
	$work = "http GET 20KB";
	$node1 = "CPU";
	$node2 = "DSK";
	$node3 = "NET";
	$node4 = "Dummy";
	$think = 1.5 * 1e-3;
	$u2demand = 0.10 * 1e-3;

	$u1pdq = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]
	$u2pdq = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]
	$u3pdq = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]
	$u1err = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]
	$u2err = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]
	$u3err = array(0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0); // array of double [11]

	// Utilization data from the paper ...

	$u1dat = array(0.0,9.0,14.0,17.0,21.0,24.0,26.0,0.0,0.0,0.0,26.0);     // this can cause division by zero error
	$u2dat = array(0.0,2.0,2.0,2.0,2.0,2.0,2.0,0.0,0.0,0.0,2.0);           // this can cause division by zero error
	$u3dat = array(0.0,26.0,46.0,61.0,74.0,86.0,92.0,0.0,0.0,0.0,94.0);    // this can cause division by zero error
	
	// Output main header ...

	printf("\n");
	printf("(Tx: \"%s\" for \"%s\")\n\n", $work, $model);
	printf("Client delay Z=%5.2f mSec. (Assumed)\n\n", $think * 1e3);
	printf("%3s\t%6s  %6s   %6s  %6s  %6s\n"," N ", "  X  ","  R  ", "%Ucpu","%Udsk","%Unet");
	printf("%3s\t%6s  %6s   %6s  %6s  %6s\n","---", "------","------", "------","------","------");

	for ($users = 1; $users <= 10; $users++) {
		PDQ_Init($model);

		$noStreams = PDQ_CreateClosed($work, TERM, (float) $users, $think);

		$noNodes = PDQ_CreateNode($node1, CEN, FCFS);
		$noNodes = PDQ_CreateNode($node2, CEN, FCFS);
		$noNodes = PDQ_CreateNode($node3, CEN, FCFS);
		$noNodes = PDQ_CreateNode($node4, CEN, FCFS);

		// NOTE: timebase is seconds

		PDQ_SetDemand($node1, $work, 0.44 * 1e-3);
		PDQ_SetDemand($node2, $work, $u2demand); /* make load-indept */
		PDQ_SetDemand($node3, $work, 1.45 * 1e-3);
		PDQ_SetDemand($node4, $work, 1.6 * 1e-3);

		PDQ_Solve(EXACT);

		// set up for error analysis of utilzations

		$u1pdq[$users] = PDQ_GetUtilization($node1, $work, TERM) * 100;
		$u2pdq[$users] = PDQ_GetUtilization($node2, $work, TERM) * 100;
		$u3pdq[$users] = PDQ_GetUtilization($node3, $work, TERM) * 100;

		$u1err[$users] = 100 * ($u1pdq[$users] - $u1dat[$users]) / $u1dat[$users]; // raise division by zero error!!
		$u2err[$users] = 100 * ($u2pdq[$users] - $u2dat[$users]) / $u2dat[$users]; // raise division by zero error!!
		$u3err[$users] = 100 * ($u3pdq[$users] - $u3dat[$users]) / $u3dat[$users]; // raise division by zero error!!

		$u2demand = 0.015 / PDQ_GetThruput(TERM, $work);
                                                                  /* http GETs-per-second */  /* milliseconds */
		printf("%3d\t%6.2f  %6.2f   %6.2f  %6.2f  %6.2f\n",$users,PDQ_GetThruput(TERM, $work),PDQ_GetResponse(TERM, $work) * 1e3,$u1pdq[$users],$u2pdq[$users],$u3pdq[$users]);

	};

	printf("\nError Analysis of Utilizations\n\n");
	printf("%3s\t%12s  %12s  %12s\n", 
			"   ", 
			"         CPU          ", 
			"         DSK          ", 
			"         NET          ");
	printf("%3s\t%12s  %12s  %12s\n", 
			"   ",
			"----------------------",
			"----------------------",
			"----------------------");

	printf("%3s    ", " N ");
	printf("%6s  %6s  %6s  ", "%Udat", "%Updq", "%Uerr");
	printf("%6s  %6s  %6s  ", "%Udat", "%Updq", "%Uerr");
	printf("%6s  %6s  %6s\n", "%Udat", "%Updq", "%Uerr");
	printf("%3s    ", "---");
	printf("%6s  %6s  %6s  ", "-----", "-----", "-----");
	printf("%6s  %6s  %6s  ", "-----", "-----", "-----");
	printf("%6s  %6s  %6s\n", "-----", "-----", "-----");

	for ($users = 1; $users <= 10; $users++) {
		if ($users <= 6 || $users == 10) {
			printf("%3d\t%5.2f\t%5.2f\t%5.2f",$users,$u1dat[$users],$u1pdq[$users],$u1err[$users]);
			printf("\t%5.2f\t%5.2f\t%5.2f",$u2dat[$users],$u2pdq[$users],$u2err[$users]);
		    printf("\t%5.2f\t%5.2f\t%5.2f\n",$u3dat[$users],$u3pdq[$users],$u3err[$users]);
		};
	};

	printf("\n");

};  // main

main();

?>