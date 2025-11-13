using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_bitcoin_etf_flows_details
    public class Cg_bitcoin_etf_flows_details
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _timestamp;
        protected string _etf_ticker;
        protected unknown _flow_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_bitcoin_etf_flows_details() { }
        public Cg_bitcoin_etf_flows_details(unknown timestamp, string etf_ticker, unknown flow_usd, unknown created_at, unknown updated_at)
        {
            this._timestamp=timestamp;
            this._etf_ticker=etf_ticker;
            this._flow_usd=flow_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Timestamp
        {
            get {return _timestamp;}
            set {_timestamp=value;}
        }
        public virtual string Etf_ticker
        {
            get {return _etf_ticker;}
            set {_etf_ticker=value;}
        }
        public virtual unknown Flow_usd
        {
            get {return _flow_usd;}
            set {_flow_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_bitcoin_etf_flows_history
    public class Cg_bitcoin_etf_flows_history
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _timestamp;
        protected unknown _flow_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_bitcoin_etf_flows_history() { }
        public Cg_bitcoin_etf_flows_history(unknown timestamp, unknown flow_usd, unknown created_at, unknown updated_at)
        {
            this._timestamp=timestamp;
            this._flow_usd=flow_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Timestamp
        {
            get {return _timestamp;}
            set {_timestamp=value;}
        }
        public virtual unknown Flow_usd
        {
            get {return _flow_usd;}
            set {_flow_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_bitcoin_etf_premium_discount_history
    public class Cg_bitcoin_etf_premium_discount_history
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _timestamp;
        protected string _ticker;
        protected unknown _nav_usd;
        protected unknown _market_price_usd;
        protected unknown _premium_discount_details;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_bitcoin_etf_premium_discount_history() { }
        public Cg_bitcoin_etf_premium_discount_history(unknown timestamp, string ticker, unknown nav_usd, unknown market_price_usd, unknown premium_discount_details, unknown created_at, unknown updated_at)
        {
            this._timestamp=timestamp;
            this._ticker=ticker;
            this._nav_usd=nav_usd;
            this._market_price_usd=market_price_usd;
            this._premium_discount_details=premium_discount_details;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Timestamp
        {
            get {return _timestamp;}
            set {_timestamp=value;}
        }
        public virtual string Ticker
        {
            get {return _ticker;}
            set {_ticker=value;}
        }
        public virtual unknown Nav_usd
        {
            get {return _nav_usd;}
            set {_nav_usd=value;}
        }
        public virtual unknown Market_price_usd
        {
            get {return _market_price_usd;}
            set {_market_price_usd=value;}
        }
        public virtual unknown Premium_discount_details
        {
            get {return _premium_discount_details;}
            set {_premium_discount_details=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_fear_greed_index
    public class Cg_fear_greed_index
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _fetch_timestamp;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_fear_greed_index() { }
        public Cg_fear_greed_index(unknown fetch_timestamp, unknown created_at, unknown updated_at)
        {
            this._fetch_timestamp=fetch_timestamp;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Fetch_timestamp
        {
            get {return _fetch_timestamp;}
            set {_fetch_timestamp=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_fear_greed_index_data_list
    public class Cg_fear_greed_index_data_list
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _fear_greed_index_id;
        protected int _index_value;
        protected int _sequence_order;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_fear_greed_index_data_list() { }
        public Cg_fear_greed_index_data_list(unknown fear_greed_index_id, int index_value, int sequence_order, unknown created_at, unknown updated_at)
        {
            this._fear_greed_index_id=fear_greed_index_id;
            this._index_value=index_value;
            this._sequence_order=sequence_order;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Fear_greed_index_id
        {
            get {return _fear_greed_index_id;}
            set {_fear_greed_index_id=value;}
        }
        public virtual int Index_value
        {
            get {return _index_value;}
            set {_index_value=value;}
        }
        public virtual int Sequence_order
        {
            get {return _sequence_order;}
            set {_sequence_order=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_funding_rate_exchange_list
    public class Cg_funding_rate_exchange_list
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _exchange;
        protected string _margin_type;
        protected unknown _funding_rate;
        protected int _funding_rate_interval;
        protected unknown _next_funding_time;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_funding_rate_exchange_list() { }
        public Cg_funding_rate_exchange_list(string symbol, string exchange, string margin_type, unknown funding_rate, int funding_rate_interval, unknown next_funding_time, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._exchange=exchange;
            this._margin_type=margin_type;
            this._funding_rate=funding_rate;
            this._funding_rate_interval=funding_rate_interval;
            this._next_funding_time=next_funding_time;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Margin_type
        {
            get {return _margin_type;}
            set {_margin_type=value;}
        }
        public virtual unknown Funding_rate
        {
            get {return _funding_rate;}
            set {_funding_rate=value;}
        }
        public virtual int Funding_rate_interval
        {
            get {return _funding_rate_interval;}
            set {_funding_rate_interval=value;}
        }
        public virtual unknown Next_funding_time
        {
            get {return _next_funding_time;}
            set {_next_funding_time=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_funding_rate_history
    public class Cg_funding_rate_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _pair;
        protected string _interval;
        protected unknown _time;
        protected unknown _open;
        protected unknown _high;
        protected unknown _low;
        protected unknown _close;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_funding_rate_history() { }
        public Cg_funding_rate_history(string exchange, string pair, string interval, unknown time, unknown open, unknown high, unknown low, unknown close, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._pair=pair;
            this._interval=interval;
            this._time=time;
            this._open=open;
            this._high=high;
            this._low=low;
            this._close=close;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Pair
        {
            get {return _pair;}
            set {_pair=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Open
        {
            get {return _open;}
            set {_open=value;}
        }
        public virtual unknown High
        {
            get {return _high;}
            set {_high=value;}
        }
        public virtual unknown Low
        {
            get {return _low;}
            set {_low=value;}
        }
        public virtual unknown Close
        {
            get {return _close;}
            set {_close=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_futures_basis_history
    public class Cg_futures_basis_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _pair;
        protected string _interval;
        protected unknown _time;
        protected unknown _open_basis;
        protected unknown _close_basis;
        protected unknown _open_change;
        protected unknown _close_change;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_futures_basis_history() { }
        public Cg_futures_basis_history(string exchange, string pair, string interval, unknown time, unknown open_basis, unknown close_basis, unknown open_change, unknown close_change, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._pair=pair;
            this._interval=interval;
            this._time=time;
            this._open_basis=open_basis;
            this._close_basis=close_basis;
            this._open_change=open_change;
            this._close_change=close_change;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Pair
        {
            get {return _pair;}
            set {_pair=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Open_basis
        {
            get {return _open_basis;}
            set {_open_basis=value;}
        }
        public virtual unknown Close_basis
        {
            get {return _close_basis;}
            set {_close_basis=value;}
        }
        public virtual unknown Open_change
        {
            get {return _open_change;}
            set {_open_change=value;}
        }
        public virtual unknown Close_change
        {
            get {return _close_change;}
            set {_close_change=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_hyperliquid_whale_alert
    public class Cg_hyperliquid_whale_alert
    {
        #region Member Variables
        protected unknown _id;
        protected string _user;
        protected string _symbol;
        protected unknown _position_size;
        protected unknown _entry_price;
        protected unknown _liq_price;
        protected unknown _position_value_usd;
        protected bool _position_action;
        protected unknown _create_time;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_hyperliquid_whale_alert() { }
        public Cg_hyperliquid_whale_alert(string user, string symbol, unknown position_size, unknown entry_price, unknown liq_price, unknown position_value_usd, bool position_action, unknown create_time, unknown created_at, unknown updated_at)
        {
            this._user=user;
            this._symbol=symbol;
            this._position_size=position_size;
            this._entry_price=entry_price;
            this._liq_price=liq_price;
            this._position_value_usd=position_value_usd;
            this._position_action=position_action;
            this._create_time=create_time;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string User
        {
            get {return _user;}
            set {_user=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual unknown Position_size
        {
            get {return _position_size;}
            set {_position_size=value;}
        }
        public virtual unknown Entry_price
        {
            get {return _entry_price;}
            set {_entry_price=value;}
        }
        public virtual unknown Liq_price
        {
            get {return _liq_price;}
            set {_liq_price=value;}
        }
        public virtual unknown Position_value_usd
        {
            get {return _position_value_usd;}
            set {_position_value_usd=value;}
        }
        public virtual bool Position_action
        {
            get {return _position_action;}
            set {_position_action=value;}
        }
        public virtual unknown Create_time
        {
            get {return _create_time;}
            set {_create_time=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_liquidation_aggregated_history
    public class Cg_liquidation_aggregated_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _interval;
        protected unknown _time;
        protected unknown _aggregated_long_liquidation_usd;
        protected unknown _aggregated_short_liquidation_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_liquidation_aggregated_history() { }
        public Cg_liquidation_aggregated_history(string symbol, string interval, unknown time, unknown aggregated_long_liquidation_usd, unknown aggregated_short_liquidation_usd, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._interval=interval;
            this._time=time;
            this._aggregated_long_liquidation_usd=aggregated_long_liquidation_usd;
            this._aggregated_short_liquidation_usd=aggregated_short_liquidation_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Aggregated_long_liquidation_usd
        {
            get {return _aggregated_long_liquidation_usd;}
            set {_aggregated_long_liquidation_usd=value;}
        }
        public virtual unknown Aggregated_short_liquidation_usd
        {
            get {return _aggregated_short_liquidation_usd;}
            set {_aggregated_short_liquidation_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_liquidation_heatmap
    public class Cg_liquidation_heatmap
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _range;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_liquidation_heatmap() { }
        public Cg_liquidation_heatmap(string symbol, string range, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._range=range;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Range
        {
            get {return _range;}
            set {_range=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_liquidation_heatmap_leverage_data
    public class Cg_liquidation_heatmap_leverage_data
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _liquidation_heatmap_id;
        protected int _sequence_order;
        protected int _x_position;
        protected int _y_position;
        protected unknown _liquidation_amount;
        protected unknown _created_at;
        #endregion
        #region Constructors
        public Cg_liquidation_heatmap_leverage_data() { }
        public Cg_liquidation_heatmap_leverage_data(unknown liquidation_heatmap_id, int sequence_order, int x_position, int y_position, unknown liquidation_amount, unknown created_at)
        {
            this._liquidation_heatmap_id=liquidation_heatmap_id;
            this._sequence_order=sequence_order;
            this._x_position=x_position;
            this._y_position=y_position;
            this._liquidation_amount=liquidation_amount;
            this._created_at=created_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Liquidation_heatmap_id
        {
            get {return _liquidation_heatmap_id;}
            set {_liquidation_heatmap_id=value;}
        }
        public virtual int Sequence_order
        {
            get {return _sequence_order;}
            set {_sequence_order=value;}
        }
        public virtual int X_position
        {
            get {return _x_position;}
            set {_x_position=value;}
        }
        public virtual int Y_position
        {
            get {return _y_position;}
            set {_y_position=value;}
        }
        public virtual unknown Liquidation_amount
        {
            get {return _liquidation_amount;}
            set {_liquidation_amount=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_liquidation_heatmap_price_candlesticks
    public class Cg_liquidation_heatmap_price_candlesticks
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _liquidation_heatmap_id;
        protected int _sequence_order;
        protected unknown _timestamp;
        protected unknown _open_price;
        protected unknown _high_price;
        protected unknown _low_price;
        protected unknown _close_price;
        protected unknown _volume;
        protected unknown _created_at;
        #endregion
        #region Constructors
        public Cg_liquidation_heatmap_price_candlesticks() { }
        public Cg_liquidation_heatmap_price_candlesticks(unknown liquidation_heatmap_id, int sequence_order, unknown timestamp, unknown open_price, unknown high_price, unknown low_price, unknown close_price, unknown volume, unknown created_at)
        {
            this._liquidation_heatmap_id=liquidation_heatmap_id;
            this._sequence_order=sequence_order;
            this._timestamp=timestamp;
            this._open_price=open_price;
            this._high_price=high_price;
            this._low_price=low_price;
            this._close_price=close_price;
            this._volume=volume;
            this._created_at=created_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Liquidation_heatmap_id
        {
            get {return _liquidation_heatmap_id;}
            set {_liquidation_heatmap_id=value;}
        }
        public virtual int Sequence_order
        {
            get {return _sequence_order;}
            set {_sequence_order=value;}
        }
        public virtual unknown Timestamp
        {
            get {return _timestamp;}
            set {_timestamp=value;}
        }
        public virtual unknown Open_price
        {
            get {return _open_price;}
            set {_open_price=value;}
        }
        public virtual unknown High_price
        {
            get {return _high_price;}
            set {_high_price=value;}
        }
        public virtual unknown Low_price
        {
            get {return _low_price;}
            set {_low_price=value;}
        }
        public virtual unknown Close_price
        {
            get {return _close_price;}
            set {_close_price=value;}
        }
        public virtual unknown Volume
        {
            get {return _volume;}
            set {_volume=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_liquidation_heatmap_y_axis
    public class Cg_liquidation_heatmap_y_axis
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _liquidation_heatmap_id;
        protected unknown _price_level;
        protected int _sequence_order;
        protected unknown _created_at;
        #endregion
        #region Constructors
        public Cg_liquidation_heatmap_y_axis() { }
        public Cg_liquidation_heatmap_y_axis(unknown liquidation_heatmap_id, unknown price_level, int sequence_order, unknown created_at)
        {
            this._liquidation_heatmap_id=liquidation_heatmap_id;
            this._price_level=price_level;
            this._sequence_order=sequence_order;
            this._created_at=created_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Liquidation_heatmap_id
        {
            get {return _liquidation_heatmap_id;}
            set {_liquidation_heatmap_id=value;}
        }
        public virtual unknown Price_level
        {
            get {return _price_level;}
            set {_price_level=value;}
        }
        public virtual int Sequence_order
        {
            get {return _sequence_order;}
            set {_sequence_order=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_long_short_global_account_ratio_history
    public class Cg_long_short_global_account_ratio_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _pair;
        protected string _interval;
        protected unknown _time;
        protected unknown _global_account_long_percent;
        protected unknown _global_account_short_percent;
        protected unknown _global_account_long_short_ratio;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_long_short_global_account_ratio_history() { }
        public Cg_long_short_global_account_ratio_history(string exchange, string pair, string interval, unknown time, unknown global_account_long_percent, unknown global_account_short_percent, unknown global_account_long_short_ratio, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._pair=pair;
            this._interval=interval;
            this._time=time;
            this._global_account_long_percent=global_account_long_percent;
            this._global_account_short_percent=global_account_short_percent;
            this._global_account_long_short_ratio=global_account_long_short_ratio;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Pair
        {
            get {return _pair;}
            set {_pair=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Global_account_long_percent
        {
            get {return _global_account_long_percent;}
            set {_global_account_long_percent=value;}
        }
        public virtual unknown Global_account_short_percent
        {
            get {return _global_account_short_percent;}
            set {_global_account_short_percent=value;}
        }
        public virtual unknown Global_account_long_short_ratio
        {
            get {return _global_account_long_short_ratio;}
            set {_global_account_long_short_ratio=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_long_short_top_account_ratio_history
    public class Cg_long_short_top_account_ratio_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _pair;
        protected string _interval;
        protected unknown _time;
        protected unknown _top_account_long_percent;
        protected unknown _top_account_short_percent;
        protected unknown _top_account_long_short_ratio;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_long_short_top_account_ratio_history() { }
        public Cg_long_short_top_account_ratio_history(string exchange, string pair, string interval, unknown time, unknown top_account_long_percent, unknown top_account_short_percent, unknown top_account_long_short_ratio, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._pair=pair;
            this._interval=interval;
            this._time=time;
            this._top_account_long_percent=top_account_long_percent;
            this._top_account_short_percent=top_account_short_percent;
            this._top_account_long_short_ratio=top_account_long_short_ratio;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Pair
        {
            get {return _pair;}
            set {_pair=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Top_account_long_percent
        {
            get {return _top_account_long_percent;}
            set {_top_account_long_percent=value;}
        }
        public virtual unknown Top_account_short_percent
        {
            get {return _top_account_short_percent;}
            set {_top_account_short_percent=value;}
        }
        public virtual unknown Top_account_long_short_ratio
        {
            get {return _top_account_long_short_ratio;}
            set {_top_account_long_short_ratio=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_open_interest_aggregated_history
    public class Cg_open_interest_aggregated_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _interval;
        protected unknown _time;
        protected unknown _open;
        protected unknown _high;
        protected unknown _low;
        protected unknown _close;
        protected string _unit;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_open_interest_aggregated_history() { }
        public Cg_open_interest_aggregated_history(string symbol, string interval, unknown time, unknown open, unknown high, unknown low, unknown close, string unit, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._interval=interval;
            this._time=time;
            this._open=open;
            this._high=high;
            this._low=low;
            this._close=close;
            this._unit=unit;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Open
        {
            get {return _open;}
            set {_open=value;}
        }
        public virtual unknown High
        {
            get {return _high;}
            set {_high=value;}
        }
        public virtual unknown Low
        {
            get {return _low;}
            set {_low=value;}
        }
        public virtual unknown Close
        {
            get {return _close;}
            set {_close=value;}
        }
        public virtual string Unit
        {
            get {return _unit;}
            set {_unit=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_open_interest_aggregated_stablecoin_history
    public class Cg_open_interest_aggregated_stablecoin_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange_list;
        protected string _symbol;
        protected string _interval;
        protected unknown _time;
        protected unknown _open;
        protected unknown _high;
        protected unknown _low;
        protected unknown _close;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_open_interest_aggregated_stablecoin_history() { }
        public Cg_open_interest_aggregated_stablecoin_history(string exchange_list, string symbol, string interval, unknown time, unknown open, unknown high, unknown low, unknown close, unknown created_at, unknown updated_at)
        {
            this._exchange_list=exchange_list;
            this._symbol=symbol;
            this._interval=interval;
            this._time=time;
            this._open=open;
            this._high=high;
            this._low=low;
            this._close=close;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange_list
        {
            get {return _exchange_list;}
            set {_exchange_list=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Open
        {
            get {return _open;}
            set {_open=value;}
        }
        public virtual unknown High
        {
            get {return _high;}
            set {_high=value;}
        }
        public virtual unknown Low
        {
            get {return _low;}
            set {_low=value;}
        }
        public virtual unknown Close
        {
            get {return _close;}
            set {_close=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_option_exchange_oi_history
    public class Cg_option_exchange_oi_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _unit;
        protected string _range;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_option_exchange_oi_history() { }
        public Cg_option_exchange_oi_history(string symbol, string unit, string range, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._unit=unit;
            this._range=range;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Unit
        {
            get {return _unit;}
            set {_unit=value;}
        }
        public virtual string Range
        {
            get {return _range;}
            set {_range=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_option_exchange_oi_history_exchange_data
    public class Cg_option_exchange_oi_history_exchange_data
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _option_exchange_oi_history_id;
        protected int _timestamp_index;
        protected string _exchange;
        protected unknown _open_interest;
        protected unknown _created_at;
        #endregion
        #region Constructors
        public Cg_option_exchange_oi_history_exchange_data() { }
        public Cg_option_exchange_oi_history_exchange_data(unknown option_exchange_oi_history_id, int timestamp_index, string exchange, unknown open_interest, unknown created_at)
        {
            this._option_exchange_oi_history_id=option_exchange_oi_history_id;
            this._timestamp_index=timestamp_index;
            this._exchange=exchange;
            this._open_interest=open_interest;
            this._created_at=created_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Option_exchange_oi_history_id
        {
            get {return _option_exchange_oi_history_id;}
            set {_option_exchange_oi_history_id=value;}
        }
        public virtual int Timestamp_index
        {
            get {return _timestamp_index;}
            set {_timestamp_index=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual unknown Open_interest
        {
            get {return _open_interest;}
            set {_open_interest=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_option_exchange_oi_history_time_list
    public class Cg_option_exchange_oi_history_time_list
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _option_exchange_oi_history_id;
        protected int _timestamp_index;
        protected unknown _timestamp;
        protected unknown _price;
        protected unknown _created_at;
        #endregion
        #region Constructors
        public Cg_option_exchange_oi_history_time_list() { }
        public Cg_option_exchange_oi_history_time_list(unknown option_exchange_oi_history_id, int timestamp_index, unknown timestamp, unknown price, unknown created_at)
        {
            this._option_exchange_oi_history_id=option_exchange_oi_history_id;
            this._timestamp_index=timestamp_index;
            this._timestamp=timestamp;
            this._price=price;
            this._created_at=created_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Option_exchange_oi_history_id
        {
            get {return _option_exchange_oi_history_id;}
            set {_option_exchange_oi_history_id=value;}
        }
        public virtual int Timestamp_index
        {
            get {return _timestamp_index;}
            set {_timestamp_index=value;}
        }
        public virtual unknown Timestamp
        {
            get {return _timestamp;}
            set {_timestamp=value;}
        }
        public virtual unknown Price
        {
            get {return _price;}
            set {_price=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_aggregated_taker_volume_history
    public class Cg_spot_aggregated_taker_volume_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange_name;
        protected string _symbol;
        protected string _interval;
        protected string _unit;
        protected unknown _time;
        protected unknown _aggregated_buy_volume_usd;
        protected unknown _aggregated_sell_volume_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_aggregated_taker_volume_history() { }
        public Cg_spot_aggregated_taker_volume_history(string exchange_name, string symbol, string interval, string unit, unknown time, unknown aggregated_buy_volume_usd, unknown aggregated_sell_volume_usd, unknown created_at, unknown updated_at)
        {
            this._exchange_name=exchange_name;
            this._symbol=symbol;
            this._interval=interval;
            this._unit=unit;
            this._time=time;
            this._aggregated_buy_volume_usd=aggregated_buy_volume_usd;
            this._aggregated_sell_volume_usd=aggregated_sell_volume_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange_name
        {
            get {return _exchange_name;}
            set {_exchange_name=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual string Unit
        {
            get {return _unit;}
            set {_unit=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Aggregated_buy_volume_usd
        {
            get {return _aggregated_buy_volume_usd;}
            set {_aggregated_buy_volume_usd=value;}
        }
        public virtual unknown Aggregated_sell_volume_usd
        {
            get {return _aggregated_sell_volume_usd;}
            set {_aggregated_sell_volume_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_coins_markets
    public class Cg_spot_coins_markets
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected unknown _current_price;
        protected unknown _market_cap;
        protected unknown _price_change_m;
        protected unknown _price_change_m;
        protected unknown _price_change_m;
        protected unknown _price_change_h;
        protected unknown _price_change_h;
        protected unknown _price_change_h;
        protected unknown _price_change_h;
        protected unknown _price_change_w;
        protected unknown _price_change_percent_m;
        protected unknown _price_change_percent_m;
        protected unknown _price_change_percent_m;
        protected unknown _price_change_percent_h;
        protected unknown _price_change_percent_h;
        protected unknown _price_change_percent_h;
        protected unknown _price_change_percent_h;
        protected unknown _price_change_percent_w;
        protected unknown _volume_usd_h;
        protected unknown _volume_usd_m;
        protected unknown _volume_usd_m;
        protected unknown _volume_usd_m;
        protected unknown _volume_usd_h;
        protected unknown _volume_usd_h;
        protected unknown _volume_usd_h;
        protected unknown _volume_usd_w;
        protected unknown _volume_change_usd_h;
        protected unknown _volume_change_usd_m;
        protected unknown _volume_change_usd_m;
        protected unknown _volume_change_usd_m;
        protected unknown _volume_change_usd_h;
        protected unknown _volume_change_usd_h;
        protected unknown _volume_change_usd_h;
        protected unknown _volume_change_usd_w;
        protected unknown _volume_change_percent_h;
        protected unknown _volume_change_percent_m;
        protected unknown _volume_change_percent_m;
        protected unknown _volume_change_percent_m;
        protected unknown _volume_change_percent_h;
        protected unknown _volume_change_percent_h;
        protected unknown _volume_change_percent_h;
        protected unknown _volume_change_percent_w;
        protected unknown _buy_volume_usd_h;
        protected unknown _buy_volume_usd_m;
        protected unknown _buy_volume_usd_m;
        protected unknown _buy_volume_usd_m;
        protected unknown _buy_volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _buy_volume_usd_w;
        protected unknown _sell_volume_usd_h;
        protected unknown _sell_volume_usd_m;
        protected unknown _sell_volume_usd_m;
        protected unknown _sell_volume_usd_m;
        protected unknown _sell_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _sell_volume_usd_w;
        protected unknown _volume_flow_usd_h;
        protected unknown _volume_flow_usd_m;
        protected unknown _volume_flow_usd_m;
        protected unknown _volume_flow_usd_m;
        protected unknown _volume_flow_usd_h;
        protected unknown _volume_flow_usd_h;
        protected unknown _volume_flow_usd_h;
        protected unknown _volume_flow_usd_w;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_coins_markets() { }
        public Cg_spot_coins_markets(string symbol, unknown current_price, unknown market_cap, unknown price_change_m, unknown price_change_m, unknown price_change_m, unknown price_change_h, unknown price_change_h, unknown price_change_h, unknown price_change_h, unknown price_change_w, unknown price_change_percent_m, unknown price_change_percent_m, unknown price_change_percent_m, unknown price_change_percent_h, unknown price_change_percent_h, unknown price_change_percent_h, unknown price_change_percent_h, unknown price_change_percent_w, unknown volume_usd_h, unknown volume_usd_m, unknown volume_usd_m, unknown volume_usd_m, unknown volume_usd_h, unknown volume_usd_h, unknown volume_usd_h, unknown volume_usd_w, unknown volume_change_usd_h, unknown volume_change_usd_m, unknown volume_change_usd_m, unknown volume_change_usd_m, unknown volume_change_usd_h, unknown volume_change_usd_h, unknown volume_change_usd_h, unknown volume_change_usd_w, unknown volume_change_percent_h, unknown volume_change_percent_m, unknown volume_change_percent_m, unknown volume_change_percent_m, unknown volume_change_percent_h, unknown volume_change_percent_h, unknown volume_change_percent_h, unknown volume_change_percent_w, unknown buy_volume_usd_h, unknown buy_volume_usd_m, unknown buy_volume_usd_m, unknown buy_volume_usd_m, unknown buy_volume_usd_h, unknown buy_volume_usd_h, unknown buy_volume_usd_h, unknown buy_volume_usd_w, unknown sell_volume_usd_h, unknown sell_volume_usd_m, unknown sell_volume_usd_m, unknown sell_volume_usd_m, unknown sell_volume_usd_h, unknown sell_volume_usd_h, unknown sell_volume_usd_h, unknown sell_volume_usd_w, unknown volume_flow_usd_h, unknown volume_flow_usd_m, unknown volume_flow_usd_m, unknown volume_flow_usd_m, unknown volume_flow_usd_h, unknown volume_flow_usd_h, unknown volume_flow_usd_h, unknown volume_flow_usd_w, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._current_price=current_price;
            this._market_cap=market_cap;
            this._price_change_m=price_change_m;
            this._price_change_m=price_change_m;
            this._price_change_m=price_change_m;
            this._price_change_h=price_change_h;
            this._price_change_h=price_change_h;
            this._price_change_h=price_change_h;
            this._price_change_h=price_change_h;
            this._price_change_w=price_change_w;
            this._price_change_percent_m=price_change_percent_m;
            this._price_change_percent_m=price_change_percent_m;
            this._price_change_percent_m=price_change_percent_m;
            this._price_change_percent_h=price_change_percent_h;
            this._price_change_percent_h=price_change_percent_h;
            this._price_change_percent_h=price_change_percent_h;
            this._price_change_percent_h=price_change_percent_h;
            this._price_change_percent_w=price_change_percent_w;
            this._volume_usd_h=volume_usd_h;
            this._volume_usd_m=volume_usd_m;
            this._volume_usd_m=volume_usd_m;
            this._volume_usd_m=volume_usd_m;
            this._volume_usd_h=volume_usd_h;
            this._volume_usd_h=volume_usd_h;
            this._volume_usd_h=volume_usd_h;
            this._volume_usd_w=volume_usd_w;
            this._volume_change_usd_h=volume_change_usd_h;
            this._volume_change_usd_m=volume_change_usd_m;
            this._volume_change_usd_m=volume_change_usd_m;
            this._volume_change_usd_m=volume_change_usd_m;
            this._volume_change_usd_h=volume_change_usd_h;
            this._volume_change_usd_h=volume_change_usd_h;
            this._volume_change_usd_h=volume_change_usd_h;
            this._volume_change_usd_w=volume_change_usd_w;
            this._volume_change_percent_h=volume_change_percent_h;
            this._volume_change_percent_m=volume_change_percent_m;
            this._volume_change_percent_m=volume_change_percent_m;
            this._volume_change_percent_m=volume_change_percent_m;
            this._volume_change_percent_h=volume_change_percent_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._volume_change_percent_w=volume_change_percent_w;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._buy_volume_usd_m=buy_volume_usd_m;
            this._buy_volume_usd_m=buy_volume_usd_m;
            this._buy_volume_usd_m=buy_volume_usd_m;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._buy_volume_usd_w=buy_volume_usd_w;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._sell_volume_usd_m=sell_volume_usd_m;
            this._sell_volume_usd_m=sell_volume_usd_m;
            this._sell_volume_usd_m=sell_volume_usd_m;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._sell_volume_usd_w=sell_volume_usd_w;
            this._volume_flow_usd_h=volume_flow_usd_h;
            this._volume_flow_usd_m=volume_flow_usd_m;
            this._volume_flow_usd_m=volume_flow_usd_m;
            this._volume_flow_usd_m=volume_flow_usd_m;
            this._volume_flow_usd_h=volume_flow_usd_h;
            this._volume_flow_usd_h=volume_flow_usd_h;
            this._volume_flow_usd_h=volume_flow_usd_h;
            this._volume_flow_usd_w=volume_flow_usd_w;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual unknown Current_price
        {
            get {return _current_price;}
            set {_current_price=value;}
        }
        public virtual unknown Market_cap
        {
            get {return _market_cap;}
            set {_market_cap=value;}
        }
        public virtual unknown Price_change_m
        {
            get {return _price_change_m;}
            set {_price_change_m=value;}
        }
        public virtual unknown Price_change_m
        {
            get {return _price_change_m;}
            set {_price_change_m=value;}
        }
        public virtual unknown Price_change_m
        {
            get {return _price_change_m;}
            set {_price_change_m=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_w
        {
            get {return _price_change_w;}
            set {_price_change_w=value;}
        }
        public virtual unknown Price_change_percent_m
        {
            get {return _price_change_percent_m;}
            set {_price_change_percent_m=value;}
        }
        public virtual unknown Price_change_percent_m
        {
            get {return _price_change_percent_m;}
            set {_price_change_percent_m=value;}
        }
        public virtual unknown Price_change_percent_m
        {
            get {return _price_change_percent_m;}
            set {_price_change_percent_m=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Price_change_percent_w
        {
            get {return _price_change_percent_w;}
            set {_price_change_percent_w=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Volume_usd_m
        {
            get {return _volume_usd_m;}
            set {_volume_usd_m=value;}
        }
        public virtual unknown Volume_usd_m
        {
            get {return _volume_usd_m;}
            set {_volume_usd_m=value;}
        }
        public virtual unknown Volume_usd_m
        {
            get {return _volume_usd_m;}
            set {_volume_usd_m=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Volume_usd_w
        {
            get {return _volume_usd_w;}
            set {_volume_usd_w=value;}
        }
        public virtual unknown Volume_change_usd_h
        {
            get {return _volume_change_usd_h;}
            set {_volume_change_usd_h=value;}
        }
        public virtual unknown Volume_change_usd_m
        {
            get {return _volume_change_usd_m;}
            set {_volume_change_usd_m=value;}
        }
        public virtual unknown Volume_change_usd_m
        {
            get {return _volume_change_usd_m;}
            set {_volume_change_usd_m=value;}
        }
        public virtual unknown Volume_change_usd_m
        {
            get {return _volume_change_usd_m;}
            set {_volume_change_usd_m=value;}
        }
        public virtual unknown Volume_change_usd_h
        {
            get {return _volume_change_usd_h;}
            set {_volume_change_usd_h=value;}
        }
        public virtual unknown Volume_change_usd_h
        {
            get {return _volume_change_usd_h;}
            set {_volume_change_usd_h=value;}
        }
        public virtual unknown Volume_change_usd_h
        {
            get {return _volume_change_usd_h;}
            set {_volume_change_usd_h=value;}
        }
        public virtual unknown Volume_change_usd_w
        {
            get {return _volume_change_usd_w;}
            set {_volume_change_usd_w=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Volume_change_percent_m
        {
            get {return _volume_change_percent_m;}
            set {_volume_change_percent_m=value;}
        }
        public virtual unknown Volume_change_percent_m
        {
            get {return _volume_change_percent_m;}
            set {_volume_change_percent_m=value;}
        }
        public virtual unknown Volume_change_percent_m
        {
            get {return _volume_change_percent_m;}
            set {_volume_change_percent_m=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Volume_change_percent_w
        {
            get {return _volume_change_percent_w;}
            set {_volume_change_percent_w=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_m
        {
            get {return _buy_volume_usd_m;}
            set {_buy_volume_usd_m=value;}
        }
        public virtual unknown Buy_volume_usd_m
        {
            get {return _buy_volume_usd_m;}
            set {_buy_volume_usd_m=value;}
        }
        public virtual unknown Buy_volume_usd_m
        {
            get {return _buy_volume_usd_m;}
            set {_buy_volume_usd_m=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_w
        {
            get {return _buy_volume_usd_w;}
            set {_buy_volume_usd_w=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_m
        {
            get {return _sell_volume_usd_m;}
            set {_sell_volume_usd_m=value;}
        }
        public virtual unknown Sell_volume_usd_m
        {
            get {return _sell_volume_usd_m;}
            set {_sell_volume_usd_m=value;}
        }
        public virtual unknown Sell_volume_usd_m
        {
            get {return _sell_volume_usd_m;}
            set {_sell_volume_usd_m=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_w
        {
            get {return _sell_volume_usd_w;}
            set {_sell_volume_usd_w=value;}
        }
        public virtual unknown Volume_flow_usd_h
        {
            get {return _volume_flow_usd_h;}
            set {_volume_flow_usd_h=value;}
        }
        public virtual unknown Volume_flow_usd_m
        {
            get {return _volume_flow_usd_m;}
            set {_volume_flow_usd_m=value;}
        }
        public virtual unknown Volume_flow_usd_m
        {
            get {return _volume_flow_usd_m;}
            set {_volume_flow_usd_m=value;}
        }
        public virtual unknown Volume_flow_usd_m
        {
            get {return _volume_flow_usd_m;}
            set {_volume_flow_usd_m=value;}
        }
        public virtual unknown Volume_flow_usd_h
        {
            get {return _volume_flow_usd_h;}
            set {_volume_flow_usd_h=value;}
        }
        public virtual unknown Volume_flow_usd_h
        {
            get {return _volume_flow_usd_h;}
            set {_volume_flow_usd_h=value;}
        }
        public virtual unknown Volume_flow_usd_h
        {
            get {return _volume_flow_usd_h;}
            set {_volume_flow_usd_h=value;}
        }
        public virtual unknown Volume_flow_usd_w
        {
            get {return _volume_flow_usd_w;}
            set {_volume_flow_usd_w=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_large_orderbook_history
    public class Cg_spot_large_orderbook_history
    {
        #region Member Variables
        protected unknown _id;
        protected unknown _order_id;
        protected string _exchange_name;
        protected string _symbol;
        protected string _base_asset;
        protected string _quote_asset;
        protected unknown _limit_price;
        protected unknown _start_time;
        protected unknown _start_quantity;
        protected unknown _start_usd_value;
        protected unknown _current_quantity;
        protected unknown _current_usd_value;
        protected unknown _current_time;
        protected unknown _executed_volume;
        protected unknown _executed_usd_value;
        protected int _trade_count;
        protected bool _order_side;
        protected bool _order_state;
        protected unknown _order_end_time;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_large_orderbook_history() { }
        public Cg_spot_large_orderbook_history(unknown order_id, string exchange_name, string symbol, string base_asset, string quote_asset, unknown limit_price, unknown start_time, unknown start_quantity, unknown start_usd_value, unknown current_quantity, unknown current_usd_value, unknown current_time, unknown executed_volume, unknown executed_usd_value, int trade_count, bool order_side, bool order_state, unknown order_end_time, unknown created_at, unknown updated_at)
        {
            this._order_id=order_id;
            this._exchange_name=exchange_name;
            this._symbol=symbol;
            this._base_asset=base_asset;
            this._quote_asset=quote_asset;
            this._limit_price=limit_price;
            this._start_time=start_time;
            this._start_quantity=start_quantity;
            this._start_usd_value=start_usd_value;
            this._current_quantity=current_quantity;
            this._current_usd_value=current_usd_value;
            this._current_time=current_time;
            this._executed_volume=executed_volume;
            this._executed_usd_value=executed_usd_value;
            this._trade_count=trade_count;
            this._order_side=order_side;
            this._order_state=order_state;
            this._order_end_time=order_end_time;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual unknown Order_id
        {
            get {return _order_id;}
            set {_order_id=value;}
        }
        public virtual string Exchange_name
        {
            get {return _exchange_name;}
            set {_exchange_name=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Base_asset
        {
            get {return _base_asset;}
            set {_base_asset=value;}
        }
        public virtual string Quote_asset
        {
            get {return _quote_asset;}
            set {_quote_asset=value;}
        }
        public virtual unknown Limit_price
        {
            get {return _limit_price;}
            set {_limit_price=value;}
        }
        public virtual unknown Start_time
        {
            get {return _start_time;}
            set {_start_time=value;}
        }
        public virtual unknown Start_quantity
        {
            get {return _start_quantity;}
            set {_start_quantity=value;}
        }
        public virtual unknown Start_usd_value
        {
            get {return _start_usd_value;}
            set {_start_usd_value=value;}
        }
        public virtual unknown Current_quantity
        {
            get {return _current_quantity;}
            set {_current_quantity=value;}
        }
        public virtual unknown Current_usd_value
        {
            get {return _current_usd_value;}
            set {_current_usd_value=value;}
        }
        public virtual unknown Current_time
        {
            get {return _current_time;}
            set {_current_time=value;}
        }
        public virtual unknown Executed_volume
        {
            get {return _executed_volume;}
            set {_executed_volume=value;}
        }
        public virtual unknown Executed_usd_value
        {
            get {return _executed_usd_value;}
            set {_executed_usd_value=value;}
        }
        public virtual int Trade_count
        {
            get {return _trade_count;}
            set {_trade_count=value;}
        }
        public virtual bool Order_side
        {
            get {return _order_side;}
            set {_order_side=value;}
        }
        public virtual bool Order_state
        {
            get {return _order_state;}
            set {_order_state=value;}
        }
        public virtual unknown Order_end_time
        {
            get {return _order_end_time;}
            set {_order_end_time=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_orderbook_aggregated
    public class Cg_spot_orderbook_aggregated
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange_name;
        protected string _symbol;
        protected string _interval;
        protected string _range_percent;
        protected unknown _time;
        protected unknown _aggregated_bids_usd;
        protected unknown _aggregated_bids_quantity;
        protected unknown _aggregated_asks_usd;
        protected unknown _aggregated_asks_quantity;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_orderbook_aggregated() { }
        public Cg_spot_orderbook_aggregated(string exchange_name, string symbol, string interval, string range_percent, unknown time, unknown aggregated_bids_usd, unknown aggregated_bids_quantity, unknown aggregated_asks_usd, unknown aggregated_asks_quantity, unknown created_at, unknown updated_at)
        {
            this._exchange_name=exchange_name;
            this._symbol=symbol;
            this._interval=interval;
            this._range_percent=range_percent;
            this._time=time;
            this._aggregated_bids_usd=aggregated_bids_usd;
            this._aggregated_bids_quantity=aggregated_bids_quantity;
            this._aggregated_asks_usd=aggregated_asks_usd;
            this._aggregated_asks_quantity=aggregated_asks_quantity;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange_name
        {
            get {return _exchange_name;}
            set {_exchange_name=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual string Range_percent
        {
            get {return _range_percent;}
            set {_range_percent=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Aggregated_bids_usd
        {
            get {return _aggregated_bids_usd;}
            set {_aggregated_bids_usd=value;}
        }
        public virtual unknown Aggregated_bids_quantity
        {
            get {return _aggregated_bids_quantity;}
            set {_aggregated_bids_quantity=value;}
        }
        public virtual unknown Aggregated_asks_usd
        {
            get {return _aggregated_asks_usd;}
            set {_aggregated_asks_usd=value;}
        }
        public virtual unknown Aggregated_asks_quantity
        {
            get {return _aggregated_asks_quantity;}
            set {_aggregated_asks_quantity=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_orderbook_history
    public class Cg_spot_orderbook_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _pair;
        protected string _interval;
        protected string _range_percent;
        protected unknown _time;
        protected unknown _bids_usd;
        protected unknown _bids_quantity;
        protected unknown _asks_usd;
        protected unknown _asks_quantity;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_orderbook_history() { }
        public Cg_spot_orderbook_history(string exchange, string pair, string interval, string range_percent, unknown time, unknown bids_usd, unknown bids_quantity, unknown asks_usd, unknown asks_quantity, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._pair=pair;
            this._interval=interval;
            this._range_percent=range_percent;
            this._time=time;
            this._bids_usd=bids_usd;
            this._bids_quantity=bids_quantity;
            this._asks_usd=asks_usd;
            this._asks_quantity=asks_quantity;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Pair
        {
            get {return _pair;}
            set {_pair=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual string Range_percent
        {
            get {return _range_percent;}
            set {_range_percent=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Bids_usd
        {
            get {return _bids_usd;}
            set {_bids_usd=value;}
        }
        public virtual unknown Bids_quantity
        {
            get {return _bids_quantity;}
            set {_bids_quantity=value;}
        }
        public virtual unknown Asks_usd
        {
            get {return _asks_usd;}
            set {_asks_usd=value;}
        }
        public virtual unknown Asks_quantity
        {
            get {return _asks_quantity;}
            set {_asks_quantity=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_pairs_markets
    public class Cg_spot_pairs_markets
    {
        #region Member Variables
        protected unknown _id;
        protected string _symbol;
        protected string _exchange_name;
        protected unknown _current_price;
        protected unknown _price_change_h;
        protected unknown _price_change_percent_h;
        protected unknown _volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _volume_change_usd_h;
        protected unknown _volume_change_percent_h;
        protected unknown _net_flows_usd_h;
        protected unknown _price_change_h;
        protected unknown _price_change_percent_h;
        protected unknown _volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _volume_change_h;
        protected unknown _volume_change_percent_h;
        protected unknown _net_flows_usd_h;
        protected unknown _price_change_h;
        protected unknown _price_change_percent_h;
        protected unknown _volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _volume_change_h;
        protected unknown _volume_change_percent_h;
        protected unknown _net_flows_usd_h;
        protected unknown _price_change_h;
        protected unknown _price_change_percent_h;
        protected unknown _volume_usd_h;
        protected unknown _buy_volume_usd_h;
        protected unknown _sell_volume_usd_h;
        protected unknown _volume_change_h;
        protected unknown _volume_change_percent_h;
        protected unknown _net_flows_usd_h;
        protected unknown _price_change_w;
        protected unknown _price_change_percent_w;
        protected unknown _volume_usd_w;
        protected unknown _buy_volume_usd_w;
        protected unknown _sell_volume_usd_w;
        protected unknown _volume_change_usd_w;
        protected unknown _volume_change_percent_w;
        protected unknown _net_flows_usd_w;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_pairs_markets() { }
        public Cg_spot_pairs_markets(string symbol, string exchange_name, unknown current_price, unknown price_change_h, unknown price_change_percent_h, unknown volume_usd_h, unknown buy_volume_usd_h, unknown sell_volume_usd_h, unknown volume_change_usd_h, unknown volume_change_percent_h, unknown net_flows_usd_h, unknown price_change_h, unknown price_change_percent_h, unknown volume_usd_h, unknown buy_volume_usd_h, unknown sell_volume_usd_h, unknown volume_change_h, unknown volume_change_percent_h, unknown net_flows_usd_h, unknown price_change_h, unknown price_change_percent_h, unknown volume_usd_h, unknown buy_volume_usd_h, unknown sell_volume_usd_h, unknown volume_change_h, unknown volume_change_percent_h, unknown net_flows_usd_h, unknown price_change_h, unknown price_change_percent_h, unknown volume_usd_h, unknown buy_volume_usd_h, unknown sell_volume_usd_h, unknown volume_change_h, unknown volume_change_percent_h, unknown net_flows_usd_h, unknown price_change_w, unknown price_change_percent_w, unknown volume_usd_w, unknown buy_volume_usd_w, unknown sell_volume_usd_w, unknown volume_change_usd_w, unknown volume_change_percent_w, unknown net_flows_usd_w, unknown created_at, unknown updated_at)
        {
            this._symbol=symbol;
            this._exchange_name=exchange_name;
            this._current_price=current_price;
            this._price_change_h=price_change_h;
            this._price_change_percent_h=price_change_percent_h;
            this._volume_usd_h=volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._volume_change_usd_h=volume_change_usd_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._net_flows_usd_h=net_flows_usd_h;
            this._price_change_h=price_change_h;
            this._price_change_percent_h=price_change_percent_h;
            this._volume_usd_h=volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._volume_change_h=volume_change_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._net_flows_usd_h=net_flows_usd_h;
            this._price_change_h=price_change_h;
            this._price_change_percent_h=price_change_percent_h;
            this._volume_usd_h=volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._volume_change_h=volume_change_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._net_flows_usd_h=net_flows_usd_h;
            this._price_change_h=price_change_h;
            this._price_change_percent_h=price_change_percent_h;
            this._volume_usd_h=volume_usd_h;
            this._buy_volume_usd_h=buy_volume_usd_h;
            this._sell_volume_usd_h=sell_volume_usd_h;
            this._volume_change_h=volume_change_h;
            this._volume_change_percent_h=volume_change_percent_h;
            this._net_flows_usd_h=net_flows_usd_h;
            this._price_change_w=price_change_w;
            this._price_change_percent_w=price_change_percent_w;
            this._volume_usd_w=volume_usd_w;
            this._buy_volume_usd_w=buy_volume_usd_w;
            this._sell_volume_usd_w=sell_volume_usd_w;
            this._volume_change_usd_w=volume_change_usd_w;
            this._volume_change_percent_w=volume_change_percent_w;
            this._net_flows_usd_w=net_flows_usd_w;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Exchange_name
        {
            get {return _exchange_name;}
            set {_exchange_name=value;}
        }
        public virtual unknown Current_price
        {
            get {return _current_price;}
            set {_current_price=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Volume_change_usd_h
        {
            get {return _volume_change_usd_h;}
            set {_volume_change_usd_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Net_flows_usd_h
        {
            get {return _net_flows_usd_h;}
            set {_net_flows_usd_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Volume_change_h
        {
            get {return _volume_change_h;}
            set {_volume_change_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Net_flows_usd_h
        {
            get {return _net_flows_usd_h;}
            set {_net_flows_usd_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Volume_change_h
        {
            get {return _volume_change_h;}
            set {_volume_change_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Net_flows_usd_h
        {
            get {return _net_flows_usd_h;}
            set {_net_flows_usd_h=value;}
        }
        public virtual unknown Price_change_h
        {
            get {return _price_change_h;}
            set {_price_change_h=value;}
        }
        public virtual unknown Price_change_percent_h
        {
            get {return _price_change_percent_h;}
            set {_price_change_percent_h=value;}
        }
        public virtual unknown Volume_usd_h
        {
            get {return _volume_usd_h;}
            set {_volume_usd_h=value;}
        }
        public virtual unknown Buy_volume_usd_h
        {
            get {return _buy_volume_usd_h;}
            set {_buy_volume_usd_h=value;}
        }
        public virtual unknown Sell_volume_usd_h
        {
            get {return _sell_volume_usd_h;}
            set {_sell_volume_usd_h=value;}
        }
        public virtual unknown Volume_change_h
        {
            get {return _volume_change_h;}
            set {_volume_change_h=value;}
        }
        public virtual unknown Volume_change_percent_h
        {
            get {return _volume_change_percent_h;}
            set {_volume_change_percent_h=value;}
        }
        public virtual unknown Net_flows_usd_h
        {
            get {return _net_flows_usd_h;}
            set {_net_flows_usd_h=value;}
        }
        public virtual unknown Price_change_w
        {
            get {return _price_change_w;}
            set {_price_change_w=value;}
        }
        public virtual unknown Price_change_percent_w
        {
            get {return _price_change_percent_w;}
            set {_price_change_percent_w=value;}
        }
        public virtual unknown Volume_usd_w
        {
            get {return _volume_usd_w;}
            set {_volume_usd_w=value;}
        }
        public virtual unknown Buy_volume_usd_w
        {
            get {return _buy_volume_usd_w;}
            set {_buy_volume_usd_w=value;}
        }
        public virtual unknown Sell_volume_usd_w
        {
            get {return _sell_volume_usd_w;}
            set {_sell_volume_usd_w=value;}
        }
        public virtual unknown Volume_change_usd_w
        {
            get {return _volume_change_usd_w;}
            set {_volume_change_usd_w=value;}
        }
        public virtual unknown Volume_change_percent_w
        {
            get {return _volume_change_percent_w;}
            set {_volume_change_percent_w=value;}
        }
        public virtual unknown Net_flows_usd_w
        {
            get {return _net_flows_usd_w;}
            set {_net_flows_usd_w=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_price_history
    public class Cg_spot_price_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _symbol;
        protected string _interval;
        protected unknown _time;
        protected unknown _open;
        protected unknown _high;
        protected unknown _low;
        protected unknown _close;
        protected unknown _volume_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_price_history() { }
        public Cg_spot_price_history(string exchange, string symbol, string interval, unknown time, unknown open, unknown high, unknown low, unknown close, unknown volume_usd, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._symbol=symbol;
            this._interval=interval;
            this._time=time;
            this._open=open;
            this._high=high;
            this._low=low;
            this._close=close;
            this._volume_usd=volume_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Open
        {
            get {return _open;}
            set {_open=value;}
        }
        public virtual unknown High
        {
            get {return _high;}
            set {_high=value;}
        }
        public virtual unknown Low
        {
            get {return _low;}
            set {_low=value;}
        }
        public virtual unknown Close
        {
            get {return _close;}
            set {_close=value;}
        }
        public virtual unknown Volume_usd
        {
            get {return _volume_usd;}
            set {_volume_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_spot_taker_volume_history
    public class Cg_spot_taker_volume_history
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected string _symbol;
        protected string _interval;
        protected string _unit;
        protected unknown _time;
        protected unknown _aggregated_buy_volume_usd;
        protected unknown _aggregated_sell_volume_usd;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_spot_taker_volume_history() { }
        public Cg_spot_taker_volume_history(string exchange, string symbol, string interval, string unit, unknown time, unknown aggregated_buy_volume_usd, unknown aggregated_sell_volume_usd, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._symbol=symbol;
            this._interval=interval;
            this._unit=unit;
            this._time=time;
            this._aggregated_buy_volume_usd=aggregated_buy_volume_usd;
            this._aggregated_sell_volume_usd=aggregated_sell_volume_usd;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual string Symbol
        {
            get {return _symbol;}
            set {_symbol=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual string Unit
        {
            get {return _unit;}
            set {_unit=value;}
        }
        public virtual unknown Time
        {
            get {return _time;}
            set {_time=value;}
        }
        public virtual unknown Aggregated_buy_volume_usd
        {
            get {return _aggregated_buy_volume_usd;}
            set {_aggregated_buy_volume_usd=value;}
        }
        public virtual unknown Aggregated_sell_volume_usd
        {
            get {return _aggregated_sell_volume_usd;}
            set {_aggregated_sell_volume_usd=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cg_whale_transfer
    public class Cg_whale_transfer
    {
        #region Member Variables
        protected unknown _id;
        protected string _transaction_hash;
        protected unknown _amount_usd;
        protected unknown _asset_quantity;
        protected string _asset_symbol;
        protected string _from_address;
        protected string _to_address;
        protected string _blockchain_name;
        protected unknown _block_height;
        protected unknown _block_timestamp;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cg_whale_transfer() { }
        public Cg_whale_transfer(string transaction_hash, unknown amount_usd, unknown asset_quantity, string asset_symbol, string from_address, string to_address, string blockchain_name, unknown block_height, unknown block_timestamp, unknown created_at, unknown updated_at)
        {
            this._transaction_hash=transaction_hash;
            this._amount_usd=amount_usd;
            this._asset_quantity=asset_quantity;
            this._asset_symbol=asset_symbol;
            this._from_address=from_address;
            this._to_address=to_address;
            this._blockchain_name=blockchain_name;
            this._block_height=block_height;
            this._block_timestamp=block_timestamp;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Transaction_hash
        {
            get {return _transaction_hash;}
            set {_transaction_hash=value;}
        }
        public virtual unknown Amount_usd
        {
            get {return _amount_usd;}
            set {_amount_usd=value;}
        }
        public virtual unknown Asset_quantity
        {
            get {return _asset_quantity;}
            set {_asset_quantity=value;}
        }
        public virtual string Asset_symbol
        {
            get {return _asset_symbol;}
            set {_asset_symbol=value;}
        }
        public virtual string From_address
        {
            get {return _from_address;}
            set {_from_address=value;}
        }
        public virtual string To_address
        {
            get {return _to_address;}
            set {_to_address=value;}
        }
        public virtual string Blockchain_name
        {
            get {return _blockchain_name;}
            set {_blockchain_name=value;}
        }
        public virtual unknown Block_height
        {
            get {return _block_height;}
            set {_block_height=value;}
        }
        public virtual unknown Block_timestamp
        {
            get {return _block_timestamp;}
            set {_block_timestamp=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
namespace Newera
{
    #region Cq_exchange_inflow_cdd
    public class Cq_exchange_inflow_cdd
    {
        #region Member Variables
        protected unknown _id;
        protected string _exchange;
        protected unknown _date;
        protected string _interval;
        protected unknown _value;
        protected unknown _created_at;
        protected unknown _updated_at;
        #endregion
        #region Constructors
        public Cq_exchange_inflow_cdd() { }
        public Cq_exchange_inflow_cdd(string exchange, unknown date, string interval, unknown value, unknown created_at, unknown updated_at)
        {
            this._exchange=exchange;
            this._date=date;
            this._interval=interval;
            this._value=value;
            this._created_at=created_at;
            this._updated_at=updated_at;
        }
        #endregion
        #region Public Properties
        public virtual unknown Id
        {
            get {return _id;}
            set {_id=value;}
        }
        public virtual string Exchange
        {
            get {return _exchange;}
            set {_exchange=value;}
        }
        public virtual unknown Date
        {
            get {return _date;}
            set {_date=value;}
        }
        public virtual string Interval
        {
            get {return _interval;}
            set {_interval=value;}
        }
        public virtual unknown Value
        {
            get {return _value;}
            set {_value=value;}
        }
        public virtual unknown Created_at
        {
            get {return _created_at;}
            set {_created_at=value;}
        }
        public virtual unknown Updated_at
        {
            get {return _updated_at;}
            set {_updated_at=value;}
        }
        #endregion
    }
    #endregion
}