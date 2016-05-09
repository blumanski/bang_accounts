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
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_group_list_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						<i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<?php echo $this->Lang->write('account_groups_breadcrumbs_tolisting');?>
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
		        <li><a href="/account/user/index/"><?php echo $this->Lang->write('account_navi_list');?></a></li>
		        <li><a href="/account/user/adduser/"><?php echo $this->Lang->write('account_navi_create');?></a></li>
		        <li class="active"><a href="/account/group/index/"><?php echo $this->Lang->write('account_navi_groupslist');?></a></li>
		        <li><a href="/account/group/newgroup/"><?php echo $this->Lang->write('account_navi_groupsnew');?></a></li>
		      </ul>
			</div>
		</nav>

<?php if(isset($this->tplVar['groups']) && is_array($this->tplVar['groups']) && count($this->tplVar['groups'])) { ?>

	<div class="action-wrapper">
		<div class="table-datatables">
	              
				<div class="row">
	              	  
					<div class="col s12 m12 19">
						
						<form action="/account/group/groupaction/" method="post">
					
							<table id="account-group-table" class="responsive-table display" cellspacing="0">
								<thead>
								
			                        <tr>
			                        	<th class="action-td">Id</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                            <th><?php echo $this->Lang->write('account_group_list_data_groupname');?></th>
			                            <th><?php echo $this->Lang->write('account_group_list_data_groupdesc');?></th>
			                        </tr>
			                    </thead>
			                 
			                    <tfoot>
			                        <tr>
			                        	<th class="action-td">Id</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                            <th><?php echo $this->Lang->write('account_group_list_data_groupname');?></th>
			                            <th><?php echo $this->Lang->write('account_group_list_data_groupdesc');?></th>
			                        </tr>
			                    </tfoot>
								
								<tbody>
								
								<?php foreach($this->tplVar['groups'] AS $key => $value) { ?>
				
				
								<tr class="">
									<td class="action-td">#<?php echo (int)$value['id'];?></td>
									<td class="action-td"><a href="/account/group/editgroup/groupid/<?php echo (int)$value['id'];?>/"><i class="mdi-editor-mode-edit"></i></a></td>
									<td class="action-td">
									
									<a class="del-group" href="/account/group/deletegroup/groupid/<?php echo (int)$value['id'];?>/"><i class="mdi-action-delete"></i></a>
									
									</td>
		                            <td><?php echo htmlspecialchars($value['name'], ENT_QUOTES, 'UTF-8');?></td>
		                            <td><?php echo htmlspecialchars($value['description'], ENT_QUOTES, 'UTF-8');?></td>
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

	createDataTable($('#account-group-table'), 50)
	
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