/* for active currant page */
$(function(){
	var url = window.location.href.split('?')[0];
	var element=$('.sidebar a[href$="'+url+'"]');
	if(element) {
		var parent=$(element).parent().parent();
		if($(parent).hasClass('sidebar-menu')) {
			$(element).parent().addClass('active'); 	
		}
		else {
			$(parent).parent().addClass('active');
			$(parent).css("display","block");
		}
		$(element).addClass('sub-active');
	}
	


	/* Change password popup on click */
	$('#header-change-pass').click(function() {
		get_modaldata('Change password',base_url+"login/change_password");
	});

	/* Data table for listing pages */
	if($.fn.dataTable) {
		var height=parseInt($(window).height());
		var height=height-300;
		if(height < 500) {
			height=500;
		}

		var dataTable_opt={
			"bPaginate": false,
			"bsort": true,
			"scrollX": true,
			"scrollY": height+"px",
			"aaSorting": []
		}
		var dataTable_obj=[];
		var oTable=$('#full-height-datatable').dataTable(dataTable_opt);

		$('#datatable-search').keyup(function(){
		    oTable.fnFilter(this.value);
		});

		/* Multiple table within same page */
		dataTable_opt.scrollY=height-120+'px';
		$('.full-height-datatable').each(function(key, ele) {
			dataTable_obj[key]=$(ele).dataTable(dataTable_opt);
			/* ID of search box */
			var textbox=$(dataTable_obj[key]).attr('data-searchbox');
			if(textbox) {
				$(textbox).keyup(function(){
				    dataTable_obj[key].fnFilter(this.value);
				});
			}

		});
	}

	$('.dataTables-adv').DataTable({
			pageLength: 25,
			responsive: true,
			dom: '<"html5buttons"B>lTfgitp',
			buttons: [
			{ extend: 'copy'},
			{extend: 'csv'},
			{extend: 'excel', title: 'ExampleFile'},
			{extend: 'pdf', title: 'ExampleFile'},

			{extend: 'print',
			customize: function (win){
				$(win.document.body).addClass('white-bg');
				$(win.document.body).css('font-size', '10px');

				$(win.document.body).find('table')
				.addClass('compact')
				.css('font-size', 'inherit');
			}
		}
		]

	});


	/* Select2 plugin */
	if($.fn.select2) {
		$('.select2').select2();
		$(".select2_tags").select2({
			data:{},
		 	tags: true,
			tokenSeparators: [',', ' ']
		})
	}

	/* Form validation library functions */
	if($.fn.validate) {
		var form_element='.validate-form';
		jQuery.validate({
            modules : 'file, security',
			form:form_element,
			scrollToTopOnError:false,
			onError:function($form) {
				setTimeout(function() {
					$first_ele = $form.find('.has-error').first();
					var $ele=$first_ele.find('.form-field');
					$ele.focus();
				},10);
			}
        });

        /* Set custom message for select fields */
	    $('select',form_element).each(function() {
	    	this.oninvalid = function(e) {
	            e.target.setCustomValidity("");
	            if (!e.target.validity.valid) {
	            	var title = $(this).attr('title');
	            	var error="Please select valid from list";;	
	            	if(title) {
	            		error="Please select valid "+$(this).attr('data-title')+" from list";
	            	}
	                e.target.setCustomValidity(error);
	            }
	        };
	        this.oninput = function(e) {
	            e.target.setCustomValidity("");
	        };
	    });

	    /* ignore showing error on blur for first time  */
		var $file = $('input[type="file"]',form_element);
		$file.bind("blur", function(e) {
			var $ele=$(this)
		    setTimeout(function() {
		    	if($ele.val() == '') {
			    	$ele.parent().find('.form-error').remove();
			    	$ele.parent().removeClass('has-error');
			    	$ele.css({'border-color':''})
			    }
		    }, 100);
		});

		/* keyup catching will be changed back to body after selecting file */
		$file.bind("change", function(e) {
		    $(this).trigger('blur');
		});
	}

	/* Datepicker plugin */
	if($.fn.datepicker) {
		/*$('.datepicker').datepicker({
			format: 'dd-mm-yyyy',
			todayHighlight:true,
			autoclose:true,
		}).on('changeDate',function(e) {
			var ele=this;
			$(ele).trigger('blur');
		});*/

		$('.year-picker').datepicker({
			startView:'decade',
			minViewMode:'decade',
			format:'yyyy',
			autoclose:true,
		}).on('changeDate',function(e) {
			var ele=this;
			$(ele).trigger('blur');
		});

		$('.monthyear-picker').datepicker({
			startView:'month',
			minViewMode:'year',
			format:'MM-yyyy',
			autoclose:true,
		}).on('changeDate',function(e) {
			var ele=this;
			$(ele).trigger('blur');
		});
	}
	/*
		Editing of content javascript action
	*/
	$(document).on('click','.table-list-edit',function() {
		//var table=$(this).attr('data-table');
		var edit_url=$(this).attr('data-editurl');
		var model_title=$(this).attr('data-modeltitle');
		var id=$(this).attr('data-editid');
		if(id) {
			var load_url=base_url+edit_url+'/'+id;
			if(model_title) {
				get_modaldata(model_title,load_url);	
			}
			else {
				window.location = load_url;
			}
		}
		else {
			swal("Please select at least one record");
		}
	});

	/*
		Editing given id of content javascript action
	*/
	$(document).on('click','.table-list-add-id',function() {
		//var table=$(this).attr('data-table');
		var edit_url=$(this).attr('data-editurl');
		var model_title=$(this).attr('data-modeltitle');
		//var selected=get_all_selected(table);
		if(edit_url) {
			var load_url=base_url+edit_url;
			if(model_title) {
				get_modaldata(model_title,load_url);	
			}
			else {
				window.location = load_url;
			}
		}
		else if(selected.length > 1) {
			swal("Only one record allowed at a time");
		}
		else {
			swal("Please select at least one record");
		}
	});

	/*
		Editing given 2 id of content javascript action
	*/
	$(document).on('click','.table-list-edit-id',function() {
		//var table=$(this).attr('data-table');
		var edit_url=$(this).attr('data-editurl');
		var model_title=$(this).attr('data-modeltitle');
		var id=$(this).attr('data-editid');
		//var selected=get_all_selected(table);
		
		if(id) {
			var load_url=base_url+edit_url+'/'+id;
			if(model_title) {
				get_modaldata(model_title,load_url);	
			}
			else {
				window.location = load_url;
			}
		}
		else if(selected.length > 1) {
			swal("Only one record allowed at a time");
		}
		else {
			swal("Please select at least one record");
		}
	});

	/*
		Page Editing of content javascript action
	*/
	$(document).on('click','.table-list-editpage',function() {
		var table=$(this).attr('data-table');
		var edit_url=$(this).attr('data-editurl');
		//var model_title=$(this).attr('data-modeltitle');
		var selected=get_all_selected(table);
		if(selected.length == 1) {
			var load_url=base_url+edit_url+'/'+selected[0];
			if(load_url) {
				window.location = load_url;
			}
		}
		else if(selected.length > 1) {
			swal("Only one record allowed at a time");
		}
		else {
			swal("Please select at least one record");
		}
	});

	/* click on Delete button */
	$('.table-list-delete').click(function() {
		var table=$(this).attr('data-tableid');
		var selected=get_all_selected(table);
		var delete_url=$(this).attr('data-modelurl');
		
		if(selected.length > 1 ||  selected.length < 1) {
			swal('Only one record allowed at a time');
		}
		else {
			var load_url=base_url+delete_url;
			swal({
				title: "Are you sure to delete?",
				type: "warning",
				showCancelButton: true,
				confirmButtonColor: "#DD6B55",
				confirmButtonText: "Yes, delete it!",
				closeOnConfirm: true,
				html: true
			}, function () {
				loading();
				$.ajax({
					url: load_url,
					type: 'POST',
					data: {'id':selected},
					success : function(data){
						data=$.parseJSON(data);
						remove_loading();
						if(data.status == '1') {
							swal("Deleted!", data.message, "success");
							location.reload();
						}
						else {
							swal({
	                          type: 'error',
	                          title: 'Oops...',
	                          html: data.message
	                        });
						}
					}
				});
			});
		}
	});

	$(document).on('keypress','.number-field',function(evt) {
		evt = (evt) ? evt : window.event;
		var charCode = (evt.which) ? evt.which : evt.keyCode;
		if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !=46) {
			return false;
		}
		return true;
	});

	apply_datepicker();
	
});


/* ajax waiting */
function loading() {
	over='<div id="overlay" style="position: fixed;left: 0;top: 0;bottom: 0;right: 0;background: #000;opacity: 0.8;filter: alpha(opacity=80);z-index: 9999;"><i style="position: absolute;top: 49%;left: 49%;font-size:37px;color:#fff;-webkit-animation: fa-spin 2s infinite linear;animation: fa-spin 2s infinite linear;" class="fa fa-refresh"></i></div>';
	$('body').append(over);
}

function remove_loading() {
	$('#overlay').remove();
}

function load_popup(title,data, model) {
	if(!model) {
		model='#commanModal';
	}

	$(model+' .modal-title').html(title);
	$(model+' .modal-body').html(data);
	$(model).modal('show');
}

function get_modaldata(title,url, model) {
	$.ajax({
		type:"POST",
		url:url,
		beforeSend:function() {
			loading();
		}

	})
	.done(function(data) {
		load_popup(title,data,model);
		$('.chosen-container').css('width','240px');
	})
	.fail(function(jqXHR, textStatus) {
		alert( "Request failed: " + textStatus );
	})
	.always(function() {
		$('#overlay').remove();
	});
}

function get_all_selected(table, checkbox) {
    var checked_list = [];
    if(!checkbox) {
    	checkbox='row_id';
    }
	selector=$(table).find('input:checkbox[name^='+checkbox+']:checked');
    $(selector).each(function() {
        checked_list.push($(this).val());
    })
    return checked_list;
}

sendXhr = function (path, post_data, successCallback, errorCallback) {
	return $.ajax({
		method:"POST",
		url: base_url+path,
		data:post_data,
		timeout:20000,
		beforeSend: function(xhr) {
			if(post_data.no_loader) { }
			else {
				loading();
			}
		},
		success: function(response, status, xhr) {
			remove_loading();
			/*convert a JSON text into a JavaScript object*/
			try {
				if(successCallback) {
					successCallback(response, status, xhr);
				}
		    } 
		    catch (e) {
		    	successCallback(response, status, xhr);
		    }
		},
		error: function (xhr,status, errorThrown) {
			remove_loading();
			if(errorCallback) {
				errorCallback();
			}
		},
		always: function() {
			remove_loading();
		},
		ajaxComplete: function() {
		}
	});
};


var date_format='dd-mm-yyyy';
/* Add date picker to .datepicker */
function apply_datepicker() {
	/* add datepicker to class .datepicker */
	if($.fn.datepicker) {
		$('.datepicker').each(function(argument) {
			var default_val=$(this).val();
			var ele=this;

			/* Change date format if value is not blank and in YYYY-MM-DD format */
			if(default_val!='' && default_val.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/) && default_val!='0000-00-00') {
				/* Convert date format */
				var d = new Date(default_val);
				var curr_date = d.getDate();
				if(curr_date < 10) {
					curr_date='0'+curr_date;
				}
				var curr_month = d.getMonth();
				curr_month++;

				if(curr_month < 10) {
					curr_month='0'+curr_month;
				}
				var curr_year = d.getFullYear();
				$(ele).val(curr_date+'-'+curr_month+'-'+curr_year);
			}

			if(default_val=='0000-00-00') {
				$(ele).val('');
			}

			$(this).datepicker({
			    "autoclose":true,
			    "format":date_format,
			    "autoUpdateInput":false,
			    "keepEmptyValues":true,
			    "todayHighlight":true,
			}).on('changeDate',function(e) {
				if($(ele).attr('data-minlimit')) {
					var limit_ele=$(ele).attr('data-minlimit');
					$(limit_ele).datepicker('setStartDate',$(ele).val());
				}
				else if($(ele).attr('data-maxlimit')) {
					var limit_ele=$(ele).attr('data-maxlimit');
					$(limit_ele).datepicker('setEndDate',$(ele).val());
				}
			});
			
		});
	}
}

/*
	Return jquery
*/
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}