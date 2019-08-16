using System.Runtime.Serialization;

/// <summary>
/// DtoCallProcedure
/// </summary>

namespace WebRest.Lib.Dto.Common
{
    [DataContract]
    public class DtoCallProcedure
    {
        [DataMember]
        public string name { get; set; }

        [DataMember]
        public string jsonParams{ get; set; }

    }
}
