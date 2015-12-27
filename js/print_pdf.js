$(function() {
	
	/*inital dept array*/
	
	dept = new Array();
	dept[0] = ["102","應物","應用科"];
	dept[1] = ["011","教育","教育"];
	dept[2] = ["041","數學","數"];
	dept[3] = ["051","體育","體育"];
	dept[4] = ["061","幼教","幼兒教育"];
	dept[5] = ["071","美術","美術產業"];
	dept[6] = ["081","特教","特殊教育"];
	dept[7] = ["091","音樂","音樂"];
	dept[8] = ["101","應化","應用科"];
	dept[9] = ["111","資工甲","資訊工程"];
	dept[10] = ["112","資工乙","資訊工程"];
	dept[11] = ["121","資管","資訊管理"];
	dept[12] = ["131","英美","英美語文"];
	dept[13] = ["141","華語","華語文"];
	dept[14] = ["151","心動","身心整合與運動休閒產業"];
	dept[15] = ["161","生科","生命科"];
	dept[16] = ["171","數媒文教","數位媒體與文教產業"];
	dept[17] = ["181","公事","公共與文化事務"];
	dept[18] = ["191","文休","文化資源與休閒產業"];
	dept[19] = ["031","社教","社會科教育"];
	dept[20] = ["001","兒碩","兒童文學研究所碩士班"];
	dept[21] = ["002","幼碩","幼兒教育學系碩士班"];
	dept[22] = ["004","南島碩","公共與文化事務學系南島文化研究碩士班"];
	dept[23] = ["005","體碩","體育學系碩士班"];
	dept[24] = ["006","區域碩","公共與文化事務學系區域政策與發展研究碩士班"];
	dept[25] = ["007","特碩","特殊教育學系碩士班"];
	dept[26] = ["008","生科碩","生命科學系碩士班"];
	dept[27] = ["009","文休碩","文化資源與休閒產業學系碩士班"];
	dept[28] = ["010","教科碩","教育學系教學科技碩士班"];
	dept[29] = ["012","課教碩","教育學系課程與教學碩士班"];
	dept[30] = ["013","資管碩","資訊管理學系碩士班"];
	dept[31] = ["014","華語碩","華語文學系碩士班"];
	dept[32] = ["015","音樂碩","音樂學系碩士班"];
	dept[33] = ["200","教育博","教育學系教育研究博士班"];
	dept[34] = ["201","兒博","兒童文學研究所博士班"];
	dept[35] = ["000","教育碩","教育學系教育研究碩士班"];
	dept[36] = ["003","語碩","語文教育研究所"];
	
	depttrans = function(code){
		for(var i=0;i<dept.length;i++){
			if(code == dept[i][0]){
				return dept[i][1];
			}
		}
		return "ZZ";
	}

	depttrans_long = function(code){
		for(var i=0;i<dept.length;i++){
			if(code == dept[i][0]){
				return dept[i][2];
			}
		}
		return "ZZ";
	}
	
	$("#dept").append("<option value='' selected>無</option>");
	for(var i=0;i<dept.length;i++){
		$("#dept").append("<option value=\""+ dept[i][2] +"\">"+dept[i][2]+"學系</option>");
	}
	
	$("#dept").selectmenu('refresh');
	
	$("#sub").click(function(){
		$.post("/sports/67/handle/Route/route?action=print_pdf_link",{"data":[{dept:$("#dept").val(),grade:$("#grade").val(),name:$("#name").val(),score:$("#score").val()}]}, function(response){
			var res = $.parseJSON(response);
			res = res["result"];
			$("#link").html("<a href='"+res+"' data-role='button' target='_blank'>"+'開啟PDF'+"</a>");
		});
	});
});