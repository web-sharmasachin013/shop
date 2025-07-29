import React from "react";
import NoContent from "../extra/NoContent";
import data from "../../data";
import CartItem from "../Cart/CartItem";
import CartNumber from "../Cart/CartNumber";
import CartBuyButton from "../Cart/CartBuyButton";
import { useSelector } from "react-redux";

function Cart() {
  const { cartItems } = useSelector((state) => state.cart);
  if (cartItems.length === 0) {
    return (
      <NoContent
        text="Nothing In Your Cart!"
        btnText="Browse Products"
      ></NoContent>
    );
  }
  return (
    <div className="row py-3">
      <div className="col-12 col-md-10 col-xl-8 mx-auto">
        <div
          id="cart"
          className="border p-3 bg-white text-dark my-3 my-md-0 rounded"
        >
          <h4 className="mb-3 px-1">Cart</h4>
          <ul className="list-group mb-3">
            {cartItems.map((item) => (
              <CartItem key={item.id} item={item} />
            ))}
          </ul>
          <CartNumber />
          <CartBuyButton />
        </div>
      </div>
    </div>
  );
}

export default Cart;
