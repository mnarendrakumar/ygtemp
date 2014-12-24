<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
	<tr>
		<td align="left" valign="top">&nbsp;</td>
	</tr>
	<tr>
		<td align="left" valign="top">
		
		<table width="98%" border="1" cellspacing="1" cellpadding="1" align="center" id="the_content" class="tabborder">
			<tr>
				<td align="center" valign="top" ><h1><?php echo $heading;?></h1></td>
			</tr>
			<tr>
			  <td align="center" valign="top" class="barheading">
					<table width="100%" border="1" cellspacing="1" cellpadding="1" align="center">
						<?php
						//echo '<pre>';
						//print_r($dcr_details);
						if(!empty($dcr_details))
						{?>
								<tr>
									<td width="7%" rowspan="2" align="center" valign="top"><strong>S.No</strong></td>
									<td width="21%" rowspan="2" align="center" valign="top"><strong>Name Of Block</strong></td>
									<td width="9%" rowspan="2" align="center" valign="top"><strong>Rent / Day/ Room (Rs.) </strong><br>
								  (A)</td>
									<td width="9%" rowspan="2" align="center" valign="top"><strong>Receipt Start No.</strong></td>
									<td width="10%" rowspan="2" align="center" valign="top"><strong>Receipt Closing No</strong></td>
									<td colspan="2" align="center" valign="top"><strong>No.Of.Days</strong></td>
									<td width="6%" rowspan="2" align="center" valign="top"><strong>Total Rooms</strong></td>
									<td width="22%" rowspan="2" align="center" valign="top"><strong>Total Amount (Rs.)</strong><br>
								  (A*1*B)+(A*2*C)</td>
								</tr>
								<tr>
								  <td width="8%" align="center" valign="top">For 1 Day Booking<br>
							      (B)</td>
								  <td width="8%" align="center" valign="top">For 2 Day Booking<br>
							      (C)</td>
		              			</tr>
						<?php	
							$sno=1;
							$g_total_amount=0;
							foreach($dcr_details as $blocks=>$rep_values)
							{
								$g_total_amount += $rep_values['total_rent_amount'];
								?>
								
								<tr>
									<td width="7%"  align="center" valign="top"><strong><?php echo $sno;?></strong></td>
									<td width="21%" align="center" valign="top"><?php echo $rep_values['blockname'];?></strong></td>
									<td width="9%" align="right" valign="top"><?php echo number_format($rep_values['rent_amt'],2);?></td>
									<td width="9%" align="center" valign="top"><?php echo $rep_values['app_start'];?></td>
									<td width="10%" align="center" valign="top"><?php echo $rep_values['app_end'];?></td>
									<td width="8%" align="center"><?php echo $rep_values['1'];?></td>
                                    <td width="8%" align="center"><?php echo $rep_values['2'];?></td>
									<td width="6%" align="center" valign="top"><?php echo $rep_values['total_bookings'];?></td>
									<td width="22%" align="right" valign="top"><?php echo number_format($rep_values['total_rent_amount'],2);?></td>
								</tr>
								
							
							<?php
							$sno++;
							}
							?>
								<tr>
									<td align="right" valign="top" colspan="8"><strong>Grand Total</strong></td>
                                    <td width="22%" align="right" valign="top"><strong><?php echo number_format($g_total_amount,2);?></strong></td>
								</tr>
								
						<?php	
						}
						else
						{ ?>
								<tr>
									<td align="center" colspan="9">No Bookings Done</td>
								</tr>
						<?php
						}
						?>
					</table>
				</td>
			</tr>
			<p></p>
			<tr>
				<td align="right" valign="top" class="barheading">
					<table width="31%" align="right">
						<tr>
							<td align="center" valign="top">&nbsp;</td>
						</tr>
						<tr>
							<td align="center" valign="top">&nbsp;</td>
						</tr>
						<tr>
							<td align="center" valign="top">Authorized Signature</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td align="center" valign="top">&nbsp;</td>
	</tr>
	<tr>
		<td align="center" valign="top">
		<input type="button" name="Print" value="Print" class="button" onClick="javascript:Clean4Print('the_content')"/>&nbsp;
		</td>
	</tr>
	<tr>
		<td align="left" valign="top">&nbsp;</td>
	</tr>
</table>