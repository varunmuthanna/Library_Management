$(document).ready(function(){
	var tD = new Date();
	var datestr = "" + tD.getFullYear() + "-"+(tD.getMonth()+ 1) + "-" + tD.getDate();
	$('#date_val').text(datestr);

	$('#newmemli').click(function(){
		hideall();
		$('#newmemform').show();
	});

	$('#searchli').click(function(){
		hideall();
		$('#searchform').show();
	});

	$('#finesli').click(function(){
		hideall();
		$('#fineform').show();
	});

	$('#searchclick').click(function(){
		var value = $('#search').val();
		getbookdata(value);
	});

	$('#finesclick').click(function(){
		var value = $('#fines').val();
		getallfines(value);
		//alert(value);
	});

	$('#date_inc').click(function(){
		var datestr = $('#date_val').text();
		var td = new Date(datestr);
		var n = td.getDate();
		td.setDate(n+1);
		datestr = "" + td.getFullYear() + "-"+(td.getMonth()+ 1) + "-" + td.getDate();
		$('#date_val').text(datestr);
		updateDB();
	});

	$('#newmemclick').click(function(){
		hide_checkerr();
		var err = 0;
		var ssn = $('#ssn').val();
		if (ssn.length == 0){
			$('#checkssn').text("Enter the ssn");
			err = 1;
		}
		var fname = $('#fname').val();
		if (fname.length == 0){
			$('#checkfname').text("Enter the first name");
			err = 1;
		}
		var lname = $('#lname').val();
		if (lname.length == 0){
			$('#checklname').text("Enter the last name");
			err = 1;
		}
		var email = $('#email').val();
		if (email.length == 0){
			$('#checkemail').text("Enter the  email");
			err = 1;
		}
		var address = $('#address').val();
		if (address.length == 0){
			$('#checkaddress').text("Enter the address");
			err = 1;
		}
		var city = $('#city').val();
		if (city.length == 0){
			$('#checkcity').text("Enter the city");
			err = 1;
		}
		var state = $('#state').val();
		if (state.length == 0){
			$('#checkstate').text("Enter the state");
			err = 1;
		}
		var phone = $('#phone').val();
		if (phone.length == 0){
			$('#checkphone').text("Enter the phone");
			err = 1;
		}


		if(err != 1){
			hide_checkerr();
			var borrower={"ssn":ssn,
							"fname":fname,
							"lname":lname,
							"email":email,
							"address":address,
							"city":city,
							"state":state,
							"phone":phone};
			var senddata = JSON.stringify(borrower);

			$.ajax({
				type:'POST',
				url:"booksearch.php",
				data:{details: senddata},
				success:function(data){
					var rspns = JSON.parse(data);
					if(rspns['msg'] == "success"){
						var tablebody = "";
						$('#dispnewborrbody').empty();
						tablebody += "<tr>";
						tablebody += "<td>"+ fname +"</td>";
						tablebody += "<td>"+lname +"</td>";
						tablebody += "<td>"+ssn+"</td>";
						tablebody += "<td>"+rspns['card_id']+"</td>";
						tablebody += "<td>"+rspns['msg']+"</td>";
						tablebody += "</tr>";
						hideall();
						$('#dispnewborrbody').append(tablebody);
						$('#dispnewborrower').show();
					}else{
						hideall();
						$('#errmesg').text("Cannot add the borrower SSN "+ssn+" already exists");
						$('#errordiv').show();
					}
				}
			});
		}

		//console.log(ssn);
	});

	function hide_checkerr(){
		$('#checkssn').text("");
		$('#checkfname').text("");
		$('#checklname').text("");
		$('#checkemail').text("");
		$('#checkaddress').text("");
		$('#checkcity').text("");
		$('#checkstate').text("");
		$('#checkphone').text("");
	}

	function getbookdata(value){
		$.ajax({
			url:"/library/booksearch.php",
			data:"action=search&keyword="+value,
			success:function(data){
				//alert(data);
				if (!$.trim(data)){   
				    hideall();
					$('#errmesg').text("Could not find books for "+value);
					$('#errordiv').show();
					return;
				}
				var rspns = JSON.parse(data);
				var tablebody = "";
				$('#dispbookbody').empty();


				if(typeof rspns[0] !== 'undefined'){
					$.each(rspns,function(index){
						tablebody += "<tr>";
						tablebody += "<td>"+ rspns[index].isbn +"</td>";
						tablebody += "<td>"+rspns[index].isbn13 +"</td>";
						rspns[index].title = rspns[index].title.replace("'","&apos;");
						tablebody += "<td>"+rspns[index].title+"</td>";
						rspns[index].author = rspns[index].author.replace("'","&apos;");
						tablebody += "<td>"+rspns[index].author+"</td>";
						tablebody += "<td>"+"<img src= '"+rspns[index].cover+"' style='width:25px height:25px'>"+"</td>";
						rspns[index].publisher = rspns[index].publisher.replace("'","&apos;");
						tablebody += "<td>"+rspns[index].publisher+"</td>";
						tablebody += "<td>"+rspns[index].pages+"</td>";
						if(rspns[index].availabilty == "1"){
							tablebody += "<td> <button type='button' id='"+ rspns[index].isbn+"' onclick='confirmcheckout("+JSON.stringify(rspns[index])+")' class='btn btn-default'>checkout</button>";
						}else{
							tablebody += "<td> <button type='button' id='"+ rspns[index].isbn+"' onclick='confirmcheckin("+JSON.stringify(rspns[index])+")' class='btn btn-default'>checkIn</button>";
						}
						tablebody += "</tr>";
					});
				}else{
					if(typeof rspns['isbn'] != 'undefined'){
						tablebody += "<tr>";
						tablebody += "<td>"+ rspns['isbn'] +"</td>";
						tablebody += "<td>"+rspns['isbn13'] +"</td>";
						rspns['title'] = rspns['title'].replace("'","&apos;");
						tablebody += "<td>"+rspns['title']+"</td>";
						rspns['Author'] = rspns['Author'].replace("'","&apos;");
						tablebody += "<td>"+rspns['Author']+"</td>";
						tablebody += "<td>"+"<img src= '"+rspns['cover']+"' style='width:25px height:25px'>"+"</td>";
						rspns['publisher'] = rspns['publisher'].replace("'","&apos;");
						tablebody += "<td>"+rspns['publisher']+"</td>";
						tablebody += "<td>"+rspns['pages']+"</td>";
						if(rspns['availabilty'] == "1"){
							tablebody += "<td> <button type='button' id='"+ rspns['isbn']+"' onclick='confirmcheckout("+JSON.stringify(rspns)+")' class='btn btn-default'>checkout</button>";
						}else{
							tablebody += "<td> <button type='button' onclick='confirmcheckin("+JSON.stringify(rspns)+")' class='btn btn-default'>checkIn</button>";
						}
						tablebody += "</tr>";
					}else{
						hideall();
						$('#errmesg').text("Could not find books for "+value);
						$('#errordiv').show();
						return;

					}
				}
				hideall();
				$('#dispbookbody').append(tablebody);
				$('#dispbooks').show();
			}
		});
	}

	console.log("document ready");
});

function hideall(){
		$('#searchform').hide();
		$('#newmemform').hide();
		$('#fineform').hide();
		$('#dispbooks').hide();
		$('#confirmcheckout').hide();
		$('#dispnewborrower').hide();
		$('#errordiv').hide();
		$('#finesdiv').hide();
		$('#successdiv').hide();
	}

function confirmcheckout(rspns){

	var imgbody = "";
	imgbody += "<img src = "+rspns['cover']+"' >";
	hideall();
	$('#imgcheckout').empty();
	$('#imgcheckout').append(imgbody);
	var detailsbody = "";
	detailsbody += "<div><div>";
	detailsbody += "<span><strong> Title:</strong> "+rspns['title']+" </span>";
	detailsbody += "</div>";
	detailsbody += "<div class='form-group'>";
	detailsbody += "<input class='form-control' id='borr_id' placeholder='Enter Borrower ID'>";
	detailsbody += "</div></div>";
	detailsbody += "<div class='col-md-offset-2 col-md-6'>";
	detailsbody += "<td> <button type='button' onclick='checkoutbook(\""+rspns['isbn']+"\")' class='btn btn-default'>checkout</button>";
	detailsbody += "</div>";
	$('#checkoutdetails').empty();
	$('#checkoutdetails').append(detailsbody);
	$('#confirmcheckout').show();
}

function checkoutbook(value){
	var borrower_id = $('#borr_id').val();
	var date = $('#date_val').text();
	$.ajax({
		url:"booksearch.php",
		data:"action=checkout&isbn="+value+"&borr_id="+borrower_id+"&date="+date,
		success:function(data){
			var rspns = JSON.parse(data);
			if(rspns['msg'] == "error"){
				hideall();
				$('#errmesg').text(rspns['data']);
				$('#errordiv').show();
			}else{
				hideall();
				$('#successmesg').text(rspns['data']);
				$('#successdiv').show();
			}
		}
	});
}

function confirmcheckin(rspns){
	var date = $('#date_val').text();
	$.ajax({
		url:"booksearch.php",
		data:"action=checkin&isbn="+rspns['isbn']+"&date="+date,
		success:function(data){
			var ret = JSON.parse(data);
			var imgbody = "";
			imgbody += "<img src = "+rspns['cover']+"' >";
			hideall();
			$('#imgcheckout').empty();
			$('#imgcheckout').append(imgbody);
			
			var detailsbody = "";
			detailsbody += "<div><div>";
			detailsbody += "<span> Title:"+rspns['title']+" </span>";
			detailsbody += "</div>";
			detailsbody += "<div>";
			detailsbody += "<span> Borrower ID: "+ret['borr_id']+" </span>";
			detailsbody += "</div>";
			detailsbody += "<div>";
			detailsbody += "<span> Fines: "+ret['fine']+" </span>";
			detailsbody += "</div></div>";
			detailsbody += "<div>";
			detailsbody += "<div class='col-md-offset-2 col-md-6'>";
			detailsbody += "<td> <button type='button' onclick='checkinbook(\""+rspns['isbn']+"\")' class='btn btn-default'>checkin</button>";
			detailsbody += "</div>";
			$('#checkoutdetails').empty();
			$('#checkoutdetails').append(detailsbody);
			$('#confirmcheckout').show();
		}
	});
	//alert(rspns['isbn']);
}

function checkinbook(value){
	var date = $('#date_val').text();
	$.ajax({
		url:"booksearch.php",
		data:"action=completecheckin&isbn="+value+"&date="+date,
		success:function(data){
			hideall();
			$('#successmesg').text(data);
			$('#successdiv').show();
		}
	});
}

function updateDB(){
	var date = $('#date_val').text();
	$.ajax({
		url:"booksearch.php",
		data:"action=updatedate&date="+date,
		success:function(data){
			//alert(data);

		}
	});

}

function getallfines(value){
	$.ajax({
		url:"booksearch.php",
		data:"action=getfines&keyword="+value,
		success:function(data){
			var rspns = JSON.parse(data);
			if(typeof rspns['fullname'] != 'undefined'){
				tablebody = "";
				tablebody += "<table class='table table-bordered'>";
				tablebody += "<tr><strong>Name:</strong> "+rspns['fullname']+" <strong>SSN:</strong> "+rspns['ssn']+"</tr>";
				var loandetails = rspns['loandetails'];
				if(typeof loandetails[0] != 'undefined'){
					var total_amt = 0;
					$.each(loandetails,function(index){
						var fines = loandetails[index];
						tablebody += "<tr>";
						tablebody += "<td>loan_id: "+fines['loan_id']+"</td>";
						tablebody += "<td>ISBN: "+fines['isbn']+"</td>";
						tablebody += "<td>title: "+fines['title']+"</td>";
						tablebody += "<td>fine Amount "+fines['fine_amt']+"$</td>";
						if(fines['paid'] == 0){
							tablebody += "<td>Not Paid</td>";
							tablebody += "<td> <button type='button' onclick='checkinbook(\""+fines['isbn']+"\")' class='btn btn-default'>Pay&Checkin</button>";
							total_amt += fines['fine_amt'];
						}else{
							tablebody += "<td>paid: "+fines['paid']+"</td>";
						}
						tablebody += "</tr>";

					});
					tablebody += "<tr><strong> Total Fine Amount Due: </strong>"+total_amt+"$</tr>"					
				}else{
					tablebody += "<tr><td>No books are borrowed</td></tr>";
				}
				tablebody += "</table>";
				hideall();
				$('#finesdiv').empty();
				$('#finesdiv').append(tablebody);
				$('#finesdiv').show();
			}else if(typeof rspns[0] != 'undefined'){
				tablebody = "";
				$.each(rspns,function(index){
					var rspns1 = rspns[index];
					tablebody += "<table class='table table-bordered'>";
					tablebody += "<tr><strong>Name:</strong> "+rspns1['fullname']+" <strong>SSN:</strong> "+rspns1['ssn']+"</tr>";
					var loandetails = rspns1['loandetails'];
					if(typeof loandetails[0] != 'undefined'){
						var total_amt = 0;
						$.each(loandetails,function(index){
							var fines = loandetails[index];
							tablebody += "<tr>";
							tablebody += "<td>loan_id: "+fines['loan_id']+"</td>";
							tablebody += "<td>ISBN: "+fines['isbn']+"</td>";
							tablebody += "<td>title: "+fines['title']+"</td>";
							tablebody += "<td>fine Amount "+fines['fine_amt']+"$</td>";
							if(fines['paid'] == 0){
								tablebody += "<td>Not Paid</td>";
								tablebody += "<td> <button type='button' onclick='checkinbook(\""+fines['isbn']+"\")' class='btn btn-default'>Pay&Checkin</button>";
								total_amt += fines['fine_amt'];
							}else{
								tablebody += "<td>paid: "+fines['paid']+"</td>";
							}
							tablebody += "</tr>";

						});
						tablebody += "<tr><strong> Total Fine Amount Due: </strong>"+total_amt+"$</tr>"					
					}else{
						tablebody += "<tr><td>No books are borrowed</td></tr>";
					}
					tablebody += "</table>";
				});
				hideall();
				$('#finesdiv').empty();
				$('#finesdiv').append(tablebody);
				$('#finesdiv').show();
			}else{
				hideall();
				$('#errmesg').text("Could not find borrowers for "+value);
				$('#errordiv').show();
				return;
			}
		}
	});
}