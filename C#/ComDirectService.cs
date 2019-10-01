using log4net;
using System;
using System.Reflection;
using System.ServiceModel;
using System.ServiceModel.Activation;
using System.ServiceModel.Web;
using WebRest.Lib.RestException;
using WebRest.Lib.Dto.Common;
using System.Data.SqlClient;
using System.Data;
using Newtonsoft.Json.Linq;

namespace WebRest.Lib.Service.Common
{
    /// <summary>
    /// ComDirectService
    /// </summary>
    [ServiceContract(Namespace = "")]
    [AspNetCompatibilityRequirements(RequirementsMode = AspNetCompatibilityRequirementsMode.Allowed)]
    public class ComDirectService
    {
        private static readonly ILog Log = LogManager.GetLogger(MethodBase.GetCurrentMethod().DeclaringType);

        [WebInvoke(Method = "POST", UriTemplate = "call_procedure", ResponseFormat = WebMessageFormat.Json, BodyStyle = WebMessageBodyStyle.Bare)]
        [OperationContract]
        public string SvcCallProcedure(DtoCallProcedure dtoreq)
        {
            OutgoingWebResponseContext responsectx = WebOperationContext.Current.OutgoingResponse;

            try
            {
                using (SqlConnection con = new SqlConnection("Data Source=FSSQL1;Initial Catalog=ELBA_PreRelease_Cauzioni;Persist Security Info=True;User ID=elba_test;Password=elba_test"))
                {
                    using (SqlCommand cmd = new SqlCommand(dtoreq.name, con))
                    {
                        cmd.CommandType = CommandType.StoredProcedure;

                        dynamic Params = JObject.Parse(dtoreq.jsonParams);

                        //for(var i=0;i<Params.Count;i++)
                        //{
                        //    cmd.Parameters.Add("@"+Params[i], SqlDbType.VarChar).Value = Params[i];
                        //}

                        /*
                         
                        */

                        cmd.Parameters.Add(new SqlParameter("@RamoId_server", SqlDbType.Int));
                        cmd.Parameters["@RamoId_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@AssuntoreId_server", SqlDbType.Int));
                        cmd.Parameters["@AssuntoreId_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@AssuntoreGrado_server", SqlDbType.Int));
                        cmd.Parameters["@AssuntoreGrado_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@TipoEmissioneId_server", SqlDbType.Int));
                        cmd.Parameters["@TipoEmissioneId_server"].Value = 999;
                        cmd.Parameters.Add(new SqlParameter("@UtenteId_server", SqlDbType.Int));
                        cmd.Parameters["@UtenteId_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@PolizzaId_server", SqlDbType.Int));
                        cmd.Parameters["@PolizzaId_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@AziendaCodiceFiscale_server", SqlDbType.NVarChar, 16));
                        cmd.Parameters["@AziendaCodiceFiscale_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@ID_Modello_server", SqlDbType.NVarChar, 10));
                        cmd.Parameters["@ID_Modello_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@CodiceModello_server", SqlDbType.NVarChar, 20));
                        cmd.Parameters["@CodiceModello_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@Stato_server", SqlDbType.NVarChar, 3));
                        cmd.Parameters["@Stato_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@SottoStatoId_server", SqlDbType.Int));
                        cmd.Parameters["@SottoStatoId_server"].Value = 999;
                        cmd.Parameters.Add(new SqlParameter("@AgenziaId_server", SqlDbType.NVarChar, 3));
                        cmd.Parameters["@AgenziaId_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@SubagenziaCodice_server", SqlDbType.NVarChar, 5));
                        cmd.Parameters["@SubagenziaCodice_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@RischioCodice_server", SqlDbType.Int));
                        cmd.Parameters["@RischioCodice_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@DaData_server", SqlDbType.DateTime));
                        cmd.Parameters["@DaData_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@AData_server", SqlDbType.DateTime));
                        cmd.Parameters["@AData_server"].Value = DBNull.Value;
                        cmd.Parameters.Add(new SqlParameter("@take_server", SqlDbType.Int));
                        cmd.Parameters["@take_server"].Value = 1000;
                        cmd.Parameters.Add(new SqlParameter("@skip_server", SqlDbType.Int));
                        cmd.Parameters["@skip_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@page_server", SqlDbType.Int));
                        cmd.Parameters["@page_server"].Value = 0;
                        cmd.Parameters.Add(new SqlParameter("@pageSize_server", SqlDbType.Int));
                        cmd.Parameters["@pageSize_server"].Value = 1000;

                        con.Open();
                        var result = cmd.ExecuteReader();

                        string dtoresp = "";
                        while (result.Read())
                        {
                            Console.WriteLine(
                                "Product: {0,-25} Price: ${1,6:####.00}",
                                result["TenMostExpensiveProducts"],
                                result["UnitPrice"]);
                        }

                        result.Close();

                        return dtoresp;
                    }
                }
            }
            catch (Exception e)
            {
                throw new ServiceException(responsectx, e.Message, e);
            }

        }

        //private SqlParameter GetGenericParamsObject(string name, object data)
        //{
        //    return new SqlParameter(name, SetSqlDataType(data.GetType().ToString())) { Direction = Input, Value = data };
        //}
    }
}