<?php

	$host = 'localhost';
	$usr = 'root';
	//password is null as I am using WAMP server
	$pwd = '';
	//$db_name = 'library_mgmt';
	//name of the DB server you are using
	$db_name = 'lib_mgmtfull';
	$con = mysqli_connect($host,$usr,$pwd,$db_name);

	if(mysqli_connect_errno()){
		echo "Failed to connect: " . mysqli_connect_error();
	}

	if(isset($_GET['action'])){
		$action = $_GET['action'];
		if($action === "search"){
			$keyword = $_GET['keyword'];
			searchForKey($keyword,$con);
		}

		if($action === "checkout"){
			$isbn = $_GET['isbn'];
			$borrower_id = $_GET['borr_id'];
			$date = $_GET['date'];
			checkoutbook($isbn,$borrower_id,$date,$con);
		}

		if($action === "checkin"){
			$isbn = $_GET['isbn'];
			$date = $_GET['date'];
			getDataForCheckin($isbn,$date,$con);
		}

		if($action === "completecheckin"){
			$isbn = $_GET['isbn'];
			$date = $_GET['date'];
			completeCheckin($isbn,$date,$con);
		}

		if($action === "updatedate"){
			$date = $_GET['date'];
			updateDB($date,$con);
		}

		if($action === "getfines"){
			$keyword = $_GET['keyword'];
			getallfines($keyword,$con);
		}
	}else if(isset($_POST['details'])){
		$borrower = json_decode($_POST['details']);

		addNewMember($borrower,$con);
	}

	function is_multi($arr){
		foreach($arr as $v){
			if(is_array($v)) return true;
		}
		return false;
	}

	function searchForKey($keyword,$con){
		$keyword = mysqli_real_escape_string($con,$keyword);

		$keyarr = explode(" ", $keyword);
		$resultdata = [];
		$keys = [];
		foreach($keyarr as $value) {
			$keys[] = " like '%" .$value ."%' or";
		}
		$columns = array(" CONVERT(b.isbn, char) ", " b.title ", " a.fullname ");
		
		$query = "select DISTINCT b.*, IF(bl.loan_id is null, 1, 0) as availabilty , GROUP_CONCAT(a.fullname SEPARATOR ', ') as author from book b left join book_authors ba on ba.isbn = b.isbn left join authors a on a.author_id = ba.author_id left join book_loans bl on bl.isbn = b.isbn and bl.date_in is null where ";
		foreach($columns as $column) {
			foreach($keys as $key) {
				$query .= $column .$key;
			}
		}
		
		$query = substr($query, 0, -2);
		$query .= ' group by b.isbn, bl.loan_id';
		mysqli_query($con, 'SET CHARACTER SET utf8');
		$result1 = mysqli_query($con,$query);

		if($result1->num_rows > 0){
			while($row = mysqli_fetch_assoc($result1)){
				$resultdata[] = $row;
			}
		}
		 echo json_encode($resultdata);

	}

	function getdatafromisbn($keyword,$con){
		$query1 = "SELECT * FROM BOOK WHERE ISBN='".$keyword."';";
		$result1 = mysqli_query($con,$query1);
		if($result1->num_rows > 0){
			$query2 = "SELECT fullname FROM BOOK_AUTHORS AS b,AUTHORS AS a WHERE ISBN='".$keyword."' AND b.author_id=a.author_id;";

			$result2 = mysqli_query($con,$query2);
			$resultdata2 = [];
			if($result2->num_rows > 0){
				while($row = mysqli_fetch_assoc($result2)){
					$resultdata2[] = $row['fullname'];
				}
			}
			$resultdata = mysqli_fetch_assoc($result1);
			$temp = "";
			foreach ($resultdata2 as $data) {
				$temp .= $data.","; 
			}

			$resultdata['Author'] = substr($temp,0,-1);
			$query3 = "SELECT * FROM book_loans WHERE isbn='".$keyword."' AND date_in IS NULL;";
			$result3 = mysqli_query($con,$query3);
			if($result3->num_rows > 0){
				$resultdata['availabilty'] = "0";
			}else{
				$resultdata['availabilty'] = "1";
			}
			return $resultdata;
		}else{
			return null;
		}
	}


	function checkoutbook($isbn,$borrower_id,$date,$con){
		$return = [];
		$query1 = "SELECT * FROM BORROWER WHERE card_no='".$borrower_id."';";
		$result1 = mysqli_query($con,$query1);
		if($result1->num_rows == 0){
			$return['msg'] = "error";
			$return['data'] = "Checkout failed: borrower id ".$borrower_id." entered is not vaild";
			echo json_encode($return);
			return;
		}

		$query2 = "SELECT * FROM book_loans WHERE card_no='".$borrower_id."' AND date_in IS NULL;";
		$result2 = mysqli_query($con,$query2);
		if($result2->num_rows >= 3){
			$return['msg'] = "error";
			$return['data'] = "Checkout failed: borrower id ".$borrower_id." has already reached the max limit of 3 books";
			echo json_encode($return);
			return;
		}
		$days = 14;
		$duedatestr = strtotime("+".$days." days", strtotime($date));
		$duedate = date("Y-m-d", $duedatestr);
		$query = "INSERT INTO book_loans (isbn,card_no,date_out,due_date) VALUES ('".$isbn."','".$borrower_id."','".$date."','".$duedate."');";
		if ($con->query($query) === TRUE) {
	    	//echo "New record created successfully";
		} else {
			$return['msg'] = "error";
			$return['data'] = "Error: ". $con->error;
			echo json_encode($return);
		    return;
		}
		$return['msg'] = "success";
		$return['data'] = "check out of ".$isbn." for borrower_id ".$borrower_id." is completed and due date is ".$duedate;
		echo json_encode($return);
	    return;
	}


	function addNewMember($borrower,$con){
		$return = [];
		$query1 = "SELECT * FROM BORROWER WHERE ssn='".$borrower->ssn."';";
		$query2 = "SELECT MAX(card_no) as max FROM BORROWER;";
		$result = mysqli_query($con,$query1);
		if($result->num_rows > 0){
			$return['msg'] = "error";
			$return['data'] = "ssn ".$borrower->ssn." already exists";
		}else{
			$result2 = mysqli_query($con,$query2);
			$result2 = mysqli_fetch_assoc($result2);
			$result = mysqli_fetch_assoc($result);
			$oldcardId = $result2['max'];
			$newcard_num = intval($oldcardId) + 1;
			$num_zeroes = strlen($oldcardId) - strlen((string)$newcard_num);
			$newcard_id = "".substr($oldcardId, 0,$num_zeroes).$newcard_num;
			$query3 = "INSERT INTO BORROWER VALUES ('".$newcard_id."','".$borrower->ssn."','".$borrower->fname."','".$borrower->lname."','".$borrower->email."','".$borrower->address."','".$borrower->city."','".$borrower->state."','".$borrower->phone."');";

			if ($con->query($query3) === TRUE) {
		    	$return['msg'] = "success";
				$return['card_id'] = $newcard_id;
				$return['data'] = "borrower ".$borrower->fname.$borrower->lname." added and new ID is ".$newcard_id;
			} else {
				$return['msg'] = "error";
				$return['data'] = "Error: ". $con->error;
			}
		}
		echo json_encode($return);
	}

	function getDataForCheckin($isbn,$date,$con){

		$query3 = "SELECT * FROM book_loans WHERE isbn='".$isbn."' AND date_in IS NULL;";
		$result3 = mysqli_query($con,$query3);
		$result3data =  mysqli_fetch_assoc($result3);
		$resultdata = [];
		$resultdata['borr_id'] = $result3data['card_no'];
		$query4 = "SELECT * FROM fines WHERE loan_id=".$result3data['loan_id'].";";
		$result4 = mysqli_query($con,$query4);
		if($result4->num_rows > 0){
			$result4data =  mysqli_fetch_assoc($result4);
			$resultdata['fine'] = "".$result4data['fine_amt']."$";
		}else{
			$resultdata['fine'] = "0$";
		}
		echo json_encode($resultdata);

	}

	function completeCheckin($isbn,$date,$con){
		$err = 0;
		$query3 = "SELECT * FROM book_loans WHERE isbn='".$isbn."' AND date_in IS NULL;";
		$result3 = mysqli_query($con,$query3);
		$result3data =  mysqli_fetch_assoc($result3);

		$resultdata['borr_id'] = $result3data['card_no'];
		$query4 = "SELECT * FROM fines WHERE loan_id=".$result3data['loan_id'].";";
		$result4 = mysqli_query($con,$query4);
		if($result4->num_rows > 0){
			$paid = 1;
			$query6 = "UPDATE fines SET paid=".$paid." WHERE loan_id=".$result3data['loan_id'].";";
			if ($con->query($query6) === TRUE) {
		    	
			} else {
			    echo "Error: ". $con->error;
			}
		}

		$query5 = "UPDATE book_loans SET date_in='".$date."' WHERE isbn=".$isbn.";";
		if ($con->query($query5) === TRUE) {
	    	//echo "New record created successfully";
		} else {
		    echo "Error: ". $con->error;
		}

		echo "checkin of ".$isbn." completed successfully";
	}

	function updateDB($date,$con){
		echo "update db";
		$query3 = "SELECT loan_id FROM book_loans WHERE due_date <= '".$date."' AND date_in IS NULL;";
		$result3 = mysqli_query($con,$query3);
		if($result3->num_rows > 0){
			while($row = mysqli_fetch_assoc($result3)){
				$loanid = $row['loan_id'];
				$query5 ="SELECT DATEDIFF('".$date."',due_date) AS DiffDate FROM book_loans WHERE loan_id=".$loanid.";";
				$result5 = mysqli_query($con,$query5);
				$result5data = mysqli_fetch_assoc($result5);
				if($result5data['DiffDate'] > 0){
					$loanamt = $result5data['DiffDate'] * 0.25;
				}else{
					$loanamt = 0;
				}
				$paid = 0;
				$query4 = "SELECT * FROM fines WHERE loan_id='".$loanid."';";
				$result4 = mysqli_query($con,$query4);
				if($result4->num_rows > 0){
					$query6 = "UPDATE fines SET fine_amt=".$loanamt." WHERE loan_id=".$loanid.";";
				}else{
					$query6 = "INSERT INTO fines (loan_id,fine_amt,paid) VALUES (".$loanid.",".$loanamt.",".$paid.");";
				}

				if ($con->query($query6) === TRUE) {
		    		//echo "New record created successfully";
				} else {
			    	echo "Error: ". $con->error;
				}
			}
		}else{
			//echo "no update";
		}

	}

	function getallfines($keyword,$con){
		$returndata = [];
		if(ctype_digit($keyword)){
			$card_no = $keyword;
			$query3 = "SELECT * FROM borrower WHERE card_no='".$card_no."';";
			$result3 = mysqli_query($con,$query3);
			if($result3->num_rows > 0){
				$result3data = mysqli_fetch_assoc($result3);
				$returndata['fullname'] = "".$result3data['Fname']." ".$result3data['Lname'];
				$returndata['ssn'] = "".$result3data['ssn'];
				$query4 = "SELECT * FROM book_loans WHERE card_no='".$card_no."';";
				$result4 = mysqli_query($con,$query4);
				$loandetails = []; 
				if($result4->num_rows > 0){
					while($row = mysqli_fetch_assoc($result4)){
						$loanid = $row['loan_id'];
						$isbn = $row['isbn'];
						$query6 = "SELECT * FROM BOOK WHERE isbn='".$isbn."';";
						$result6 = mysqli_query($con,$query6);
						$result6data = mysqli_fetch_assoc($result6);
						$fines = [];
						$fines['loan_id'] = $loanid;
						$fines['isbn'] = $isbn;
						$fines['title'] = $result6data['title'];
						$query5 = "SELECT fine_amt, paid FROM fines WHERE loan_id=".$loanid.";";
						$result5 = mysqli_query($con,$query5);
						if($result5->num_rows > 0){
							$result5data = mysqli_fetch_assoc($result5);
							$fines['fine_amt'] = $result5data['fine_amt'];
							$fines['paid'] = $result5data['paid'];
						}else{
							$fines['fine_amt'] = 0;
							if($row['date_in'] != NULL){
								$fines['paid'] = 1;
							}else{
								$fines['paid'] = 0;
							}
							
						}
						$loandetails[] = $fines;
					}
					$returndata['loandetails'] = $loandetails;
				}else{
					$returndata['loandetails'] = $loandetails;
				}
				echo json_encode($returndata);

			}else{
				echo json_encode($returndata);
			}
		}else{
			$keyarr = explode(" ", $keyword);
			if(sizeof($keyarr) > 2){
				echo json_encode($returndata);
				return;
			}else if(sizeof($keyarr) == 2){
				$fname = $keyarr[0];
				$lname = $keyarr[1];
				$query3 = "SELECT * FROM borrower WHERE Fname='".$fname."' AND Lname='".$lname."';";
				$result3 = mysqli_query($con,$query3);
				if($result3->num_rows > 0){
					while($result3data = mysqli_fetch_assoc($result3)){
						$card_no = $result3data['card_no'];
						$returndata1['fullname'] = "".$result3data['Fname']." ".$result3data['Lname'];
						$returndata1['ssn'] = "".$result3data['ssn'];
						$query4 = "SELECT * FROM book_loans WHERE card_no='".$card_no."';";
						$result4 = mysqli_query($con,$query4);
						$loandetails = []; 
						if($result4->num_rows > 0){
							while($row = mysqli_fetch_assoc($result4)){
								$loanid = $row['loan_id'];
								$isbn = $row['isbn'];
								$query6 = "SELECT * FROM BOOK WHERE isbn='".$isbn."';";
								$result6 = mysqli_query($con,$query6);
								$result6data = mysqli_fetch_assoc($result6);
								$fines = [];
								$fines['loan_id'] = $loanid;
								$fines['isbn'] = $isbn;
								$fines['title'] = $result6data['title'];
								$query5 = "SELECT fine_amt, paid FROM fines WHERE loan_id=".$loanid.";";
								$result5 = mysqli_query($con,$query5);
								if($result5->num_rows > 0){
									$result5data = mysqli_fetch_assoc($result5);
									$fines['fine_amt'] = $result5data['fine_amt'];
									$fines['paid'] = $result5data['paid'];
								}else{
									$fines['fine_amt'] = 0;
									if($row['date_in'] != NULL){
										$fines['paid'] = 1;
									}else{
										$fines['paid'] = 0;
									}
									
								}
								$loandetails[] = $fines;
							}
							$returndata1['loandetails'] = $loandetails;
						}else{
							$returndata1['loandetails'] = $loandetails;
						}

						$returndata[] = $returndata1;
					}
				}
				echo json_encode($returndata);
			}else{
				$name = $keyword;
				$query3 = "SELECT * FROM borrower WHERE Fname='".$name."';";
				$result3 = mysqli_query($con,$query3);
				if($result3->num_rows > 0){
					while($result3data = mysqli_fetch_assoc($result3)){
						$card_no = $result3data['card_no'];
						$returndata1['fullname'] = "".$result3data['Fname']." ".$result3data['Lname'];
						$returndata1['ssn'] = "".$result3data['ssn'];
						$query4 = "SELECT * FROM book_loans WHERE card_no='".$card_no."';";
						$result4 = mysqli_query($con,$query4);
						$loandetails = []; 
						if($result4->num_rows > 0){
							while($row = mysqli_fetch_assoc($result4)){
								$loanid = $row['loan_id'];
								$isbn = $row['isbn'];
								$query6 = "SELECT * FROM BOOK WHERE isbn='".$isbn."';";
								$result6 = mysqli_query($con,$query6);
								$result6data = mysqli_fetch_assoc($result6);
								$fines = [];
								$fines['loan_id'] = $loanid;
								$fines['isbn'] = $isbn;
								$fines['title'] = $result6data['title'];
								$query5 = "SELECT fine_amt, paid FROM fines WHERE loan_id=".$loanid.";";
								$result5 = mysqli_query($con,$query5);
								if($result5->num_rows > 0){
									$result5data = mysqli_fetch_assoc($result5);
									$fines['fine_amt'] = $result5data['fine_amt'];
									$fines['paid'] = $result5data['paid'];
								}else{
									$fines['fine_amt'] = 0;
									if($row['date_in'] != NULL){
										$fines['paid'] = 1;
									}else{
										$fines['paid'] = 0;
									}
									
								}
								$loandetails[] = $fines;
							}
							$returndata1['loandetails'] = $loandetails;
						}else{
							$returndata1['loandetails'] = $loandetails;
						}

						$returndata[] = $returndata1;
					}
				}

				$query3 = "SELECT * FROM borrower WHERE Lname='".$name."';";
				$result3 = mysqli_query($con,$query3);
				if($result3->num_rows > 0){
					while($result3data = mysqli_fetch_assoc($result3)){
						$card_no = $result3data['card_no'];
						$returndata1['fullname'] = "".$result3data['Fname']." ".$result3data['Lname'];
						$returndata1['ssn'] = "".$result3data['ssn'];
						$query4 = "SELECT * FROM book_loans WHERE card_no='".$card_no."';";
						$result4 = mysqli_query($con,$query4);
						$loandetails = []; 
						if($result4->num_rows > 0){
							while($row = mysqli_fetch_assoc($result4)){
								$loanid = $row['loan_id'];
								$isbn = $row['isbn'];
								$query6 = "SELECT * FROM BOOK WHERE isbn='".$isbn."';";
								$result6 = mysqli_query($con,$query6);
								$result6data = mysqli_fetch_assoc($result6);
								$fines = [];
								$fines['loan_id'] = $loanid;
								$fines['isbn'] = $isbn;
								$fines['title'] = $result6data['title'];
								$query5 = "SELECT fine_amt, paid FROM fines WHERE loan_id=".$loanid.";";
								$result5 = mysqli_query($con,$query5);
								if($result5->num_rows > 0){
									$result5data = mysqli_fetch_assoc($result5);
									$fines['fine_amt'] = $result5data['fine_amt'];
									$fines['paid'] = $result5data['paid'];
								}else{
									$fines['fine_amt'] = 0;
									if($row['date_in'] != NULL){
										$fines['paid'] = 1;
									}else{
										$fines['paid'] = 0;
									}
									
								}
								$loandetails[] = $fines;
							}
							$returndata1['loandetails'] = $loandetails;
						}else{
							$returndata1['loandetails'] = $loandetails;
						}

						$returndata[] = $returndata1;
					}
				}
				echo json_encode($returndata);		
			}
		}
	}
?>