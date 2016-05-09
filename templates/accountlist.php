<!--breadcrumbs start-->
<div id="breadcrumbs-wrapper">
	<!-- Search for small screen -->
	<div class="header-search-wrapper grey hide-on-large-only">
		<i class="mdi-action-search active"></i>
			<input type="text" name="Search" class="header-search-input z-depth-2" placeholder="Explore Materialize">
	</div>
	
	<div class="container">
	
		<div class="row">
			<div class="col s12 m12 l12">
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_listing_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						<i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<?php echo $this->Lang->write('account_listing_breadcrumbs_tolisting');?>
					</li>
				</ol>
			</div>
		</div>
	</div>
</div>
<!--breadcrumbs end-->
        
<!--start container-->
<div class="container content-wrapper">
	<div class="section">

		<nav>
			<div class="nav-wrapper">
				<ul id="nav-mobile" class="left hide-on-med-and-down">
		        <li class="active"><a href="/account/user/index/"><?php echo $this->Lang->write('account_navi_list');?></a></li>
		        <li><a href="/account/user/adduser/"><?php echo $this->Lang->write('account_navi_create');?></a></li>
		        <li><a href="/account/group/index/"><?php echo $this->Lang->write('account_navi_groupslist');?></a></li>
		        <li><a href="/account/group/newgroup/"><?php echo $this->Lang->write('account_navi_groupsnew');?></a></li>
		      </ul>
			</div>
		</nav>

<?php if(isset($this->tplVar['accounts']) && is_array($this->tplVar['accounts']) && count($this->tplVar['accounts'])) { ?>

	<div class="action-wrapper">
		<div class="table-datatables">
	              
				<div class="row">
					<div class="col s12 m12 19">
					
						<form action="/account/user/useraction/" method="post">
					
							<table id="account-data-table" class="responsive-table display" cellspacing="0">
								<thead>
			                        <tr>
			                        	<th class="action-td">Id</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_username');?></th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_email');?></th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_created');?></th>
			                        </tr>
			                    </thead>
			                 
			                    <tfoot>
			                        <tr>
			                        	<th class="action-td">Id</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_username');?></th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_email');?></th>
			                            <th><?php echo $this->Lang->write('account_listing_table_header_created');?></th>
			                        </tr>
			                    </tfoot>
								
								<tbody>
								
								<?php foreach($this->tplVar['accounts'] AS $key => $value) { ?>
				
								<?php
								$del = false;
								if(isset($value['userDeleted']) && $value['userDeleted'] == 1) { 
									$del = true;
								}
								?>
				
								<tr class="<?php if($del === true) { echo 'user-removed'; }?>">
									<td class="action-td"><?php echo (int)$value['userId'];?></td>
									<td class="action-td"><a href="/account/user/edituser/userid/<?php echo (int)$value['userId'];?>/"><i class="mdi-editor-mode-edit"></i></a></td>
									<td class="action-td">
									<?php if($del === true) { ?>
										<a href="/account/user/activateuser/userid/<?php echo (int)$value['userId'];?>/"><i class="mdi-av-loop" style="color: #00aa00 !important;"></i></a>
									<?php } else { ?>
										<a href="/account/user/deleteuser/userid/<?php echo (int)$value['userId'];?>/"><i class="mdi-action-delete"></i></a>
									<?php } ?>
									</td>
		                            <td><?php echo htmlspecialchars($value['userUsername'], ENT_QUOTES, 'UTF-8');?></td>
		                            <td><?php echo htmlspecialchars($value['userEmail'], ENT_QUOTES, 'UTF-8');?></td>
		                            <td><time data-format="L" datetime="<?php echo htmlspecialchars($value['userCreated'], ENT_QUOTES, 'UTF-8');?>"></time>
		                            </td>
		                        </tr>
								<?php } ?>
		
		                    </tbody>
		                  </table>
	                  
	                  </form>
	                  
	                </div>
	              </div>
	            </div> 
            </div>

<?php } ?>


	</div>
</div>
            
<script>

$(document).ready(function() {

	createDataTable($('#account-data-table'), 50, 0)
	
//     $('#account-data-table').DataTable( {
//         "paging":   true,
//         "ordering": true,
//         "info":     true,
//         "bDestroy":true,
//         "bPaginate": true,
//         "bLengthChange": true,
//         "bFilter": false,
//         "iDisplayLength": max,
//         "bSort": true,
//         "bAutoWidth": false
//     } );
} );


</script>