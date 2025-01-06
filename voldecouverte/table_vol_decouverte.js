// Table tarifs vol decuvertes
document.write("<table style='width: 100%; margin-left: auto; margin-right: auto;' border='2' cellspacing='3'>");
document.write("<tbody>");
document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
document.write("<td>Circuits</td>");
document.write("<td>Time (min)</td>");
document.write("<td colspan='2'>Normal Fee (&euro;)</td>");
document.write("<td colspan='2'>Member Fee (&euro;)</td>");
document.write("</tr>");
document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
document.write("<td></td>");
document.write("<td></td>");
document.write("<td colspan='2'>Passengers</td>");
document.write("<td colspan='2'>Passengers</td>");
document.write("</tr>");
document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
document.write("<td></td>");
document.write("<td></td>");
document.write("<td>1</td>");
document.write("<td>2-3</td>");
document.write("<td>1</td>");
document.write("<td>2-3</td>");
document.write("</tr>");
for (var i = 0;  i < my_offrir_circuits.length; i++) {
  document.write("<tr>");
  var aName=my_offrir_circuits[i].name;
  document.write("<td>"+aName+"</td>");
  var aTime =  my_offrir_circuits[i].tarif;
  document.write("<td style='text-align: right;'>"+aTime+"\47</td>");
  document.write("<td style='text-align: right;'>"+compute_tarif("decouverte_1_passager", aTime)+"&euro;</td>");
  document.write("<td style='text-align: right;'>"+compute_tarif("decouverte_2_passager", aTime)+"&euro;</td>");
  document.write("<td style='text-align: right;'>"+compute_tarif("membre_1_passager", aTime)+"&euro;</td>");
  document.write("<td style='text-align: right;'>"+compute_tarif("membre_2_passager", aTime)+"&euro;</td>");
  document.write("</tr>");
}		
document.write("</tbody>");
document.write("</table>");
