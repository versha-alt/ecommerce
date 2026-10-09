/* Visual payment badges; never enable or alter checkout methods. */
export default function PaymentBadges({methods=[]}:{methods?:{name?:string;provider?:string}[]}){
 const enabled=methods.map(method=>(method.name+' '+method.provider).toLowerCase()).join(' ');
 return <div className="payment-badges" aria-label="Payment options"><span className="payment-badge mpesa">M-Pesa</span><span className="payment-badge visa">VISA</span><span className="payment-badge mastercard"><i aria-hidden="true"/><span>Mastercard</span></span><small>{enabled?'Available methods shown at checkout':'Confirm payment availability at checkout'}</small></div>;
}
