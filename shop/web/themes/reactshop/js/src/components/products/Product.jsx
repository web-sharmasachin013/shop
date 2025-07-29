import React from "react";
import Price from "../extra/Price";
import ProductButton from "./ProductButton";
import { useNavigate } from "react-router-dom";

function Product(props) {
  const nav = useNavigate();
  const { product } = props;
  const imgPath = "/images/" + product.id + ".jpg";
  const handleClick = () => {
    nav(`/single/${product.id}`);
  };

  return (
    <div className="col">
      <div className="card product-card h-100" id="product">
        <img
          onClick={handleClick}
          src={imgPath}
          alt=""
          className="w-100 card-img-top pointer"
        />
        <div className="card-body p-4">
          <div className="text-center ">
            <h6>{product.name}</h6>
            <span className="product-price">
              <Price value={product.price} decimals={2}></Price>
            </span>
          </div>
        </div>
        <div className="card-footer p-4 pt-0 border-top-0 bg-transparent">
          <ProductButton product={product} />
        </div>
      </div>
    </div>
  );
}

export default Product;
