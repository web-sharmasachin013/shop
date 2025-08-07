import React from "react";
import NoContent from "../extra/NoContent";
import data from "../../data";
import CartItem from "../Cart/CartItem";
import CartNumber from "../Cart/CartNumber";
import CartBuyButton from "../Cart/CartBuyButton";
import { useSelector } from "react-redux";

function Cart() {
  const { cartItems } = useSelector((state) => state.cart);
  const { products } = useSelector((state) => state.products);

  // const getProductsByCartItems = (cartItems, products) => {
  //   const ids = cartItems.map((item) => item.id);
  //   const idSet = new Set(ids); // faster lookup
  //   return products.filter((product) => idSet.has(String(product.id)));
  // };

  const mergeCartWithProducts = (cartItems, products) => {
    return cartItems
      .map((cartItem) => {
        const product = products.find(
          (p) => String(p.id) === String(cartItem.id)
        );
        if (!product) return null;

        return {
          ...product,
          quantity: cartItem.quantity, // quantity from cart
          uuid: cartItem.uuid,
          order_id: cartItem.order_id,
          order_item_id: cartItem.order_item_id,
        };
      })
      .filter(Boolean); // removes nulls if product not found
  };

  const cartProducts = mergeCartWithProducts(cartItems, products);
  console.log(cartProducts);

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
            {cartProducts.map((item) => (
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
